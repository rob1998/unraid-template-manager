<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

final class TemplateInventoryService
{
    private string $templatesDir;

    public function __construct(string $templatesDir)
    {
        $this->templatesDir = $templatesDir;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTemplates(): array
    {
        if (!is_dir($this->templatesDir)) {
            return [];
        }

        $files = glob($this->templatesDir . '/*.xml') ?: [];
        natcasesort($files);

        $rows = [];
        foreach ($files as $file) {
            $rows[] = $this->buildRow($file);
        }

        return $rows;
    }

    public function templatesPathExists(): bool
    {
        return is_dir($this->templatesDir);
    }

    public function getTemplatesDir(): string
    {
        return $this->templatesDir;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRow(string $filePath): array
    {
        $filename = basename($filePath);
        $fallbackName = preg_replace('/\.xml$/i', '', $filename) ?: $filename;

        $row = [
            'filename' => $filename,
            'name' => $fallbackName,
            'image' => '',
            'network' => '',
            'status' => 'valid',
            'error' => '',
            'size' => (int) (@filesize($filePath) ?: 0),
            'modified' => (int) (@filemtime($filePath) ?: 0),
        ];

        $xmlText = @file_get_contents($filePath);
        if ($xmlText === false) {
            $row['status'] = 'invalid';
            $row['error'] = 'Unable to read file contents.';
            return $row;
        }

        $previousSetting = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlText);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        if ($xml === false) {
            $row['status'] = 'invalid';
            $row['error'] = $this->formatXmlError($errors);
            return $row;
        }

        $name = trim((string) ($xml->Name ?? ''));
        $image = trim((string) ($xml->Repository ?? ''));
        $network = trim((string) ($xml->Network ?? ''));

        if ($name !== '') {
            $row['name'] = $name;
        }
        $row['image'] = $image;
        $row['network'] = $network;

        return $row;
    }

    /**
     * @param array<int, \LibXMLError> $errors
     */
    private function formatXmlError(array $errors): string
    {
        if (count($errors) === 0) {
            return 'Malformed XML';
        }

        $first = $errors[0];
        $message = trim((string) $first->message);
        $line = (int) $first->line;
        $column = (int) $first->column;

        if ($line > 0) {
            return sprintf('%s (line %d, col %d)', $message, $line, $column);
        }

        return $message;
    }
}

