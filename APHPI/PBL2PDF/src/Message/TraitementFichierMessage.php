<?php
namespace App\Message;

class TraitementFichierMessage
{
    private $filePath;

    public function __construct(string $filePath, string $headerPath, string $footerPath)
    {
        $this->filePath = $filePath;
        $this->headerPath = $headerPath;
        $this->footerPath = $footerPath;
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
