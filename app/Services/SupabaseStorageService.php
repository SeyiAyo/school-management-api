<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
     * Generate a signed URL for private Supabase storage access
     *
     * @param string $filePath The file path in the bucket (e.g., 'logos/file.jpg')
     * @param int $expiresIn Expiration time in seconds (default: 1 hour)
     * @return string|null The signed URL or null if failed
     */
    public function generateSignedUrl($filePath, $expiresIn = 3600)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->serviceKey}",
                'Content-Type' => 'application/json'
            ])->post("{$this->supabaseUrl}/storage/v1/object/sign/{$this->bucket}/{$filePath}", [
                'expiresIn' => $expiresIn
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->supabaseUrl . $data['signedURL'];
            }

            Log::warning('Failed to generate Supabase signed URL', [
                'file_path' => $filePath,
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
    public function getFileUrl($filePath, $useSignedUrl = false, $expiresIn = 3600)
    {
        if (!$filePath) {
            return null;
        }

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
            return $file->store($folder, 'supabase');
        } catch (\Exception $e) {
            Log::error('Error storing file to Supabase: ' . $e->getMessage());
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
            if (Storage::disk('supabase')->exists($filePath)) {
                return Storage::disk('supabase')->delete($filePath);
            }
            return true; // File doesn't exist, consider it deleted
        } catch (\Exception $e) {
            Log::error('Error deleting file from Supabase: ' . $e->getMessage(), [
                'file_path' => $filePath
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
