<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SupabaseStorageService
{
    protected $supabaseUrl;
    protected $serviceKey;
    protected $bucket;

    public function __construct()
    {
        $this->supabaseUrl = env('SUPABASE_URL');
        $this->serviceKey = env('SUPABASE_ACCESS_KEY_ID');
        $this->bucket = env('SUPABASE_BUCKET', 'school-images');
    }

    /**
     * Check if Supabase is properly configured
     *
     * @return bool
     */
    protected function isConfigured(): bool
    {
        return !empty($this->supabaseUrl) && 
               !empty($this->serviceKey) && 
               !empty($this->bucket);
    }

    /**
     * Generate a signed URL for private Supabase storage access
     *
     * @param string $filePath The file path in the bucket (e.g., 'logos/file.jpg')
     * @param int $expiresIn Expiration time in seconds (default: 1 hour)
     * @return string|null The signed URL or null if failed
     */
    public function generateSignedUrl($filePath, $expiresIn = 3600)
    {
        try {
            Log::info('Generating signed URL', [
                'file_path' => $filePath,
                'bucket' => $this->bucket,
                'url' => $this->supabaseUrl
            ]);
            
            $httpClient = Http::withHeaders([
                'Authorization' => "Bearer {$this->serviceKey}",
                'Content-Type' => 'application/json'
            ]);
            
            // Disable SSL verification in development (XAMPP/Windows fix)
            if (env('APP_ENV') !== 'production') {
                $httpClient = $httpClient->withOptions(['verify' => false]);
            }
            
            $response = $httpClient->post("{$this->supabaseUrl}/storage/v1/object/sign/{$this->bucket}/{$filePath}", [
                'expiresIn' => $expiresIn
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $signedUrl = $this->supabaseUrl . $data['signedURL'];
                Log::info('Signed URL generated successfully');
                return $signedUrl;
            }

            Log::warning('Failed to generate Supabase signed URL', [
                'file_path' => $filePath,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error generating Supabase signed URL: ' . $e->getMessage(), [
                'file_path' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Generate a public URL for files in public buckets
     *
     * @param string $filePath The file path in the bucket
     * @return string The public URL
     */
    public function generatePublicUrl($filePath)
    {
        return "{$this->supabaseUrl}/storage/v1/object/public/{$this->bucket}/{$filePath}";
    }

    /**
     * Get URL for a file (public or signed based on configuration)
     *
     * @param string $filePath The file path in the bucket
     * @param bool $useSignedUrl Whether to generate signed URL (default: false for public)
     * @param int $expiresIn Expiration time for signed URLs
     * @return string|null The URL or null if failed
     */
    public function getFileUrl($filePath, $useSignedUrl = true, $expiresIn = 86400)
    {
        if (!$filePath) {
            return null;
        }

        if (!$this->isConfigured()) {
            Log::warning('Attempting to get file URL but Supabase not configured', [
                'file_path' => $filePath
            ]);
            return null;
        }

        // For private buckets, always use signed URLs (default 24 hours)
        if ($useSignedUrl) {
            return $this->generateSignedUrl($filePath, $expiresIn);
        }

        return $this->generatePublicUrl($filePath);
    }

    /**
     * Store a file to Supabase storage
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder The folder to store in (e.g., 'logos', 'profiles/users')
     * @return string|false The stored file path or false on failure
     */
    public function storeFile($file, $folder = '')
    {
        try {
            if (!$this->isConfigured()) {
                Log::error('Supabase storage not configured', [
                    'has_url' => !empty($this->supabaseUrl),
                    'has_key' => !empty($this->serviceKey),
                    'has_bucket' => !empty($this->bucket)
                ]);
                return false;
            }

            Log::info('Attempting to store file to Supabase via REST API', [
                'folder' => $folder,
                'filename' => $file->getClientOriginalName(),
                'supabase_url' => $this->supabaseUrl,
                'bucket' => $this->bucket
            ]);

            // Generate unique filename
            $filename = $folder ? $folder . '/' . uniqid() . '_' . $file->getClientOriginalName() : uniqid() . '_' . $file->getClientOriginalName();
            
            // Upload using Supabase REST API
            $httpClient = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => $file->getMimeType(),
            ]);
            
            // Disable SSL verification in development (XAMPP/Windows fix)
            if (env('APP_ENV') !== 'production') {
                $httpClient = $httpClient->withOptions(['verify' => false]);
            }
            
            $response = $httpClient->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post("{$this->supabaseUrl}/storage/v1/object/{$this->bucket}/{$filename}");

            if (!$response->successful()) {
                Log::error('Supabase upload failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'filename' => $filename
                ]);
                return false;
            }
            
            Log::info('File stored successfully via REST API', [
                'path' => $filename,
                'folder' => $folder
            ]);
            
            return $filename;
        } catch (\Exception $e) {
            Log::error('Error storing file to Supabase: ' . $e->getMessage(), [
                'folder' => $folder,
                'filename' => $file->getClientOriginalName(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Delete a file from Supabase storage
     *
     * @param string $filePath
     * @return bool Success status
     */
    public function deleteFile($filePath)
    {
        try {
            if (!$this->isConfigured()) {
                Log::warning('Attempting to delete file but Supabase not configured', [
                    'file_path' => $filePath
                ]);
                return false;
            }

            if (Storage::disk('supabase')->exists($filePath)) {
                $result = Storage::disk('supabase')->delete($filePath);
                Log::info('File deleted from Supabase', [
                    'file_path' => $filePath,
                    'success' => $result
                ]);
                return $result;
            }
            
            Log::info('File does not exist in Supabase, skipping deletion', [
                'file_path' => $filePath
            ]);
            return true; // File doesn't exist, consider it deleted
        } catch (\Exception $e) {
            Log::error('Error deleting file from Supabase: ' . $e->getMessage(), [
                'file_path' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check if a file exists in Supabase storage
     *
     * @param string $filePath
     * @return bool
     */
    public function fileExists($filePath)
    {
        try {
            return Storage::disk('supabase')->exists($filePath);
        } catch (\Exception $e) {
            Log::error('Error checking file existence in Supabase: ' . $e->getMessage());
            return false;
        }
    }
}
