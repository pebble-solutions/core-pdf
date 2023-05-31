<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Message\TraitementFichierMessage;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Operation;
use Pebble\Security\PAS\PasToken;
use Throwable;



class TraitementController extends AbstractController
{
    

    public function AuthToken(){
        $token = new PasToken();
        try {
            $token->getTokenFromAuthorizationHeader()->decode();
            return $token;
            }
          catch (Throwable $e) {
            throw new \InvalidArgumentException('Error : '.$e->getMessage());
          }
    }

    #[Route('/test', name:'app_test', methods:['GET'])]
    public function test(){
        $token=$this->AuthToken();
        return new JsonResponse(['success' => $token->getExp()]);
    }

    #[Route('/upload', name: 'app_upload', methods:['POST'])]
    public function uploadFichier(Request $request, MessageBusInterface $bus, EntityManagerInterface $entityManager): Response
    {
        try {
            $fs = new Filesystem();
            $file = $request->files->get('Fichier');
            $header = $request->files->get('Header');
            $footer = $request->files->get('Footer');
            $css = $request->files->get('Style');
            $validExtensions = ['html', 'htm'];
    
            if (!$file instanceof UploadedFile) {
                throw new \InvalidArgumentException('Le fichier HTML n\'a pas été transmis.');
            }
    
            $extensionFile = $file->getClientOriginalExtension();
    
            if (!in_array($extensionFile, $validExtensions)) {
                throw new \InvalidArgumentException('Le fichier doit être un fichier HTML ou HTM.');
            }
    
            $extensionHeader = $header ? $header->getClientOriginalExtension() : null;
            if (isset($extensionHeader) && !in_array($extensionHeader, $validExtensions)) {
                throw new \InvalidArgumentException('Le fichier de l\'en-tête doit être un fichier HTML ou HTM.');
            }
    
            $extensionFooter = $footer ? $footer->getClientOriginalExtension() : null;
            if (isset($extensionFooter) && !in_array($extensionFooter, $validExtensions)) {
                throw new \InvalidArgumentException('Le fichier de pied de page doit être un fichier HTML ou HTM.');
            }
    
            $extensionCss = $css ? $css->getClientOriginalExtension() : null;
            if (isset($extensionCss) && $extensionCss != "css") {
                throw new \InvalidArgumentException('Le fichier de style doit être un fichier CSS.');
            }
    
            $id = uniqid();
            $directory = 'uploads/' . $id;
            $baseFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $fileName = $baseFileName . '.' . $extensionFile;
            $headerName = 'header.html';
            $footerName = 'footer.html';
            $cssName = 'style.css';
    
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
    
            $existingOperations = $entityManager->getRepository(Operation::class)->findBy(['nom' => $fileName]);
            
            $counter = 1;
            while ($this->isOperationFileExists($existingOperations, $fileName, $counter)) {
                $fileName = $baseFileName . '(' . $counter . ').' . $extensionFile;
                $counter++;
                $existingOperations = $entityManager->getRepository(Operation::class)->findBy(['nom' => $fileName]);
            }
    
            $file->move(
                $directory,
                $fileName
            );
    
            if ($header) {
                $header->move(
                    $directory,
                    $headerName
                );
            } else {
                $fs->copy($this->getParameter('default_file_path'), $directory . "/$headerName");
            }
    
            if ($footer) {
                $footer->move(
                    $directory,
                    $footerName
                );
            } else {
                $fs->copy($this->getParameter('default_file_path'), $directory . "/$footerName");
            }
    
            if ($css) {
                $css->move(
                    $directory,
                    $cssName
                );
            } else {
                $fs->copy($this->getParameter('default_file_path'), $directory . "/$cssName");
            }
    
            $operation = new Operation();
            $operation->setNom($fileName);
            $operation->setEtat('Running');
            $operation->setContenuBase64('');
            $entityManager->persist($operation);
            $entityManager->flush();
            $operationId = $operation->getId();
            $bus->dispatch(new TraitementFichierMessage($operationId, $id, $fileName, $headerName, $footerName, $cssName));
    
            return new JsonResponse(['success' => 'L\'opération a été enregistrée avec succès. Votre PDF sera généré dans quelques instants.']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors du traitement du fichier.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    private function isOperationFileExists(array $existingOperations, string $fileName, int $counter): bool
    {
        $fileNameWithCounter = $fileName . '(' . $counter . ')';
        foreach ($existingOperations as $operation) {
            if ($operation->getNom() === $fileName || $operation->getNom() === $fileNameWithCounter) {
                return true;
            }
        }
        return false;
    }
    
    
    
    
    

    

    #[Route('/fichiers', name: 'app_liste_fichiers')]
    public function listeFichiers(): Response
    {
        try {
            $fichiers = scandir($this->getParameter('render_directory'));
            $fichiers = array_filter($fichiers, function ($fichier) {
                return $fichier !== '.' && $fichier !== '..';
            });

            $data = [];
            foreach ($fichiers as $fichier) {
                $data[] = [
                    'nom' => $fichier,
                ];
            }

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la récupération de la liste des fichiers.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/operations', name: 'app_liste_operations')]
    public function listeOperations(EntityManagerInterface $entityManager): Response
    {
        $token=$this->AuthToken();
        try {
            $operations = $entityManager->getRepository(Operation::class)->findAll();

            $data = [];
            foreach ($operations as $operation) {
                $data[] = [
                    'id' => $operation->getId(),
                    'fichier' => $operation->getNom() . '.pdf',
                    'etat' => $operation->getEtat(),
                ];
            }

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la récupération de la liste des opérations.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/fichier/{id}', name: 'app_fichier')]
public function afficherFichier(EntityManagerInterface $entityManager, int $id): Response
{
    $operation = $entityManager->find(Operation::class, $id);

    if (!$operation) {
        return new JsonResponse(['error' => 'L\'opération avec l\'ID spécifié n\'existe pas.'], Response::HTTP_NOT_FOUND);
    }

    $fichierPath = $this->getParameter('render_directory') . '/' . $operation->getNom() . '.pdf';

    if (file_exists($fichierPath)) {
        $data = [
            'id' => $operation->getId(),
            'fichier' => $operation->getNom() . '.pdf',
            'contenu' => $operation->getContenuBase64(),
        ];

        return new JsonResponse($data);
    }

    $data = [
        'id' => $operation->getId(),
        'fichier' => $operation->getNom() . '.pdf',
        'contenu' => '',
    ];

    return new JsonResponse($data);
}

    
    
    #[Route('/fichier/del/{id}/', name: 'app_supprimer_fichier', methods: ['DELETE'])]
    public function supprimerFichier(EntityManagerInterface $entityManager, int $id, Filesystem $filesystem): Response
    {
        try {
            $operation = $entityManager->getRepository(Operation::class)->find($id);
    
            if (!$operation) {
                $message = 'L\'opération avec l\'ID spécifié n\'existe pas.';
                return new JsonResponse(['error' => $message], Response::HTTP_NOT_FOUND);
            }
    
            $fichierPath = $this->getParameter('render_directory') . '/' . $operation->getNom() . '.pdf';
    
            if ($filesystem->exists($fichierPath)) {
                $filesystem->remove($fichierPath);
            }
    
            $entityManager->remove($operation);
            $entityManager->flush();
    
            $message = 'L\'opération et le fichier ont été supprimés avec succès.';
            return new JsonResponse(['message' => $message]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la suppression du fichier.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

}
