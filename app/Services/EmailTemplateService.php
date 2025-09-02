<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EmailTemplateService
{
    private string $templatePath;

    public function __construct()
    {
        $this->templatePath = resource_path('email-templates');
    }

    /**
     * Load and render email template with variables
     *
     * @param string $templateName Template file name (without .html extension)
     * @param array $variables Associative array of variables to replace
     * @return string Rendered HTML content
     * @throws \Exception If template file not found
     */
    public function renderTemplate(string $templateName, array $variables = []): string
    {
        $templateFile = $this->templatePath . DIRECTORY_SEPARATOR . $templateName . '.html';

        if (!File::exists($templateFile)) {
            Log::error("Email template not found: {$templateFile}");
            throw new \Exception("Email template '{$templateName}' not found");
        }

        $content = File::get($templateFile);

        // Replace template variables using {{variableName}} syntax
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        // Handle conditional blocks for Handlebars-like syntax
        $content = $this->processConditionals($content, $variables);

        return $content;
    }

    /**
     * Process conditional blocks in templates
     *
     * @param string $content Template content
     * @param array $variables Template variables
     * @return string Processed content
     */
    private function processConditionals(string $content, array $variables): string
    {
        // Handle {{#if variable}} blocks
        $pattern = '/\{\{#if\s+(\w+)\}\}(.*?)\{\{else\}\}(.*?)\{\{\/if\}\}/s';
        $content = preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variable = $matches[1];
            $ifContent = $matches[2];
            $elseContent = $matches[3];
            
            return !empty($variables[$variable]) && $variables[$variable] ? $ifContent : $elseContent;
        }, $content);

        // Handle {{#if variable}} blocks without else
        $pattern = '/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s';
        $content = preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variable = $matches[1];
            $ifContent = $matches[2];
            
            return !empty($variables[$variable]) && $variables[$variable] ? $ifContent : '';
        }, $content);

        return $content;
    }

    /**
     * Get list of available email templates
     *
     * @return array Array of template names (without .html extension)
     */
    public function getAvailableTemplates(): array
    {
        if (!File::isDirectory($this->templatePath)) {
            return [];
        }

        $files = File::files($this->templatePath);
        $templates = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'html') {
                $templates[] = $file->getFilenameWithoutExtension();
            }
        }

        return $templates;
    }
}
