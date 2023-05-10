<?php
namespace App\Message;

class TraitementFichierMessage
{
    private $filePath;

    public function __construct(string $directory, string $filePath, string $headerPath, string $footerPath)
    {
        $this->directory = $directory;
        $this->filePath = $filePath;
        $this->headerPath = $headerPath;
        $this->footerPath = $footerPath;
    }
    public function getDirectory(): string
    {
        return $this->directory;
    }
    public function getFilePath(): string
    {
        return $this->filePath;
    }
    public function getHeaderPath(): string
    {
        return $this->headerPath;
    }
    public function getFooterPath(): string
    {
        return $this->footerPath;
    }
}
