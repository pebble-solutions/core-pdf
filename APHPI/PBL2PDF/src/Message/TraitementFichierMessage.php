<?php
namespace App\Message;

use Doctrine\ORM\EntityManagerInterface;

class TraitementFichierMessage
{
    private $filePath;

    public function __construct(int $id,string $directory, string $filePath, string $headerPath, string $footerPath)
    {
        $this->id = $id;
        $this->directory = $directory;
        $this->filePath = $filePath;
        $this->headerPath = $headerPath;
        $this->footerPath = $footerPath;
    }

    public function getId(): int
    {
        return $this->id;
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
