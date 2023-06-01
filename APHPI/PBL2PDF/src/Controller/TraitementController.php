<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Message\TraitementFichierMessage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Operation;
use Pebble\Security\PAS\PasToken;
use Throwable;



class TraitementController extends AbstractController
{


    public function AuthToken()
    {
        $token = new PasToken();
        try {
            $token->getTokenFromAuthorizationHeader()->decode();
            return $token;
        } catch (Throwable $e) {
            throw new \InvalidArgumentException('Error : ' . $e->getMessage());
        }
    }

    #[Route('/test', name: 'app_test', methods: ['GET'])]
    public function test()
    {
        $token = $this->AuthToken();
        return new JsonResponse(['success' => $token->getExp()]);
    }

    #[Route('/upload', name: 'app_upload', methods: ['POST'])]
    public function uploadFichier(Request $request, MessageBusInterface $bus, EntityManagerInterface $entityManager): Response
    {
        try {
            $fs = new Filesystem();

            // Récupérer les données du formulaire
            $login = $request->get('login');
            $file = $request->files->get('Fichier');
            $header = $request->files->get('Header');
            $footer = $request->files->get('Footer');
            $css = $request->files->get('Style');
            $validExtensions = ['html', 'htm'];

            // Vérifier si le fichier HTML a été transmis
            if (!$file instanceof UploadedFile) {
                throw new \InvalidArgumentException('Le fichier HTML n\'a pas été transmis.');
            }

            $extensionFile = $file->getClientOriginalExtension();

            // Vérifier si l'extension du fichier est valide
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

            // Créer le répertoire de destination si nécessaire
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            // Vérifier s'il existe déjà des opérations avec le même nom de fichier
            $existingOperations = $entityManager->getRepository(Operation::class)->findBy(['nom' => $fileName]);

            $counter = 1;
            while ($this->isOperationFileExists($existingOperations, $fileName, $counter)) {
                $fileName = $baseFileName . '(' . $counter . ').' . $extensionFile;
                $counter++;
                $existingOperations = $entityManager->getRepository(Operation::class)->findBy(['nom' => $fileName]);
            }

            // Déplacer le fichier HTML vers le répertoire de destination
            $file->move(
                $directory,
                $fileName
            );

            // Déplacer le fichier d'en-tête vers le répertoire de destination ou utiliser un fichier par défaut
            if ($header) {
                $header->move(
                    $directory,
                    $headerName
                );
            } else {
                $fs->copy($this->getParameter('default_file_path'), $directory . "/$headerName");
            }

            // Déplacer le fichier de pied de page vers le répertoire de destination ou utiliser un fichier par défaut
            if ($footer) {
                $footer->move(
                    $directory,
                    $footerName
                );
            } else {
                $fs->copy($this->getParameter('default_file_path'), $directory . "/$footerName");
            }

            // Déplacer le fichier CSS vers le répertoire de destination ou utiliser un fichier par défaut
            if ($css) {
                $css->move(
                    $directory,
                    $cssName
                );
            } else {
                $fs->copy($this->getParameter('default_file_path'), $directory . "/$cssName");
            }

            // Créer une nouvelle opération et la sauvegarder en base de données
            $operation = new Operation();
            $operation->setNom($fileName);
            $operation->setLogin($login);
            $operation->setEtat('Running');
            $operation->setContenuBase64('');
            $entityManager->persist($operation);
            $entityManager->flush();
            $operationId = $operation->getId();

            // Envoyer un message pour démarrer le traitement du fichier
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
        // Ajoute le compteur au nom du fichier
        $fileNameWithCounter = $fileName . '(' . $counter . ')';

        // Parcourt les opérations existantes
        foreach ($existingOperations as $operation) {
            // Vérifie si le nom du fichier ou le nom du fichier avec le compteur correspond à une opération existante
            if ($operation->getNom() === $fileName || $operation->getNom() === $fileNameWithCounter) {
                return true; // Le fichier existe déjà
            }
        }

        return false; // Le fichier n'existe pas
    }

    #[Route('/fichiers', name: 'app_liste_fichiers')]
    public function listeFichiers(): Response
    {
        try {
            // Récupère la liste des fichiers dans le répertoire de rendu
            $fichiers = scandir($this->getParameter('render_directory'));

            // Filtre les fichiers '.' et '..' de la liste
            $fichiers = array_filter($fichiers, function ($fichier) {
                return $fichier !== '.' && $fichier !== '..';
            });

            $data = [];

            // Parcourt les fichiers et ajoute leurs noms à la liste de données
            foreach ($fichiers as $fichier) {
                $data[] = [
                    'nom' => $fichier,
                ];
            }

            return new JsonResponse($data); // Renvoie la liste des fichiers au format JSON
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la récupération de la liste des fichiers.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/operations/{login}', name: 'app_liste_operations_login')]
    public function listeOperationsLogin(EntityManagerInterface $entityManager, int $login): Response
    {
        try {
            // Récupère toutes les opérations depuis le gestionnaire d'entités
            $operations = $entityManager->getRepository(Operation::class)->findAll();

            $data = [];

            // Parcourt les opérations et ajoute les informations pertinentes à la liste de données
            foreach ($operations as $operation) {
                if ($login == $operation->getLogin()) {
                    $data[] = [
                        'id' => $operation->getId(),
                        'fichier' => $operation->getNom() . '.pdf',
                        'etat' => $operation->getEtat(),
                    ];
                }
            }

            return new JsonResponse($data); // Renvoie la liste des opérations au format JSON
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la récupération de la liste de vos opérations.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    #[Route('/operations', name: 'app_liste_operations')]
    public function listeOperations(EntityManagerInterface $entityManager): Response
    {
        try {
            // Récupère toutes les opérations depuis le gestionnaire d'entités
            $operations = $entityManager->getRepository(Operation::class)->findAll();

            $data = [];
            foreach ($operations as $operation) {
                // Ajoute les informations pertinentes de chaque opération à la liste de données
                $data[] = [
                    'id' => $operation->getId(),
                    'fichier' => $operation->getNom() . '.pdf',
                    'etat' => $operation->getEtat(),
                    'login' => $operation->getLogin(),
                ];
            }

            return new JsonResponse($data); // Renvoie la liste des opérations au format JSON
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la récupération de la liste des opérations.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/fichier/{id}', name: 'app_fichier')]
    public function afficherFichier(EntityManagerInterface $entityManager, int $id): Response
    {
        // Recherche l'opération avec l'ID spécifié
        $operation = $entityManager->find(Operation::class, $id);

        if (!$operation) {
            return new JsonResponse(['error' => 'L\'opération avec l\'ID spécifié n\'existe pas.'], Response::HTTP_NOT_FOUND);
        }

        $fichierPath = $this->getParameter('render_directory') . '/' . $operation->getNom() . '.pdf';

        if (file_exists($fichierPath)) {
            // Si le fichier existe, ajoute les informations de l'opération et le contenu du fichier à la réponse JSON
            $data = [
                'id' => $operation->getId(),
                'fichier' => $operation->getNom() . '.pdf',
                'contenu' => $operation->getContenuBase64(),
            ];

            return new JsonResponse($data);
        }

        // Si le fichier n'existe pas, renvoie les informations de l'opération avec un contenu vide dans la réponse JSON
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
            // Recherche l'opération avec l'ID spécifié
            $operation = $entityManager->getRepository(Operation::class)->find($id);

            if (!$operation) {
                $message = 'L\'opération avec l\'ID spécifié n\'existe pas.';
                return new JsonResponse(['error' => $message], Response::HTTP_NOT_FOUND);
            }

            $fichierPath = $this->getParameter('render_directory') . '/' . $operation->getNom() . '.pdf';

            if ($filesystem->exists($fichierPath)) {
                // Si le fichier existe, le supprime
                $filesystem->remove($fichierPath);
            }

            // Supprime l'opération de la base de données
            $entityManager->remove($operation);
            $entityManager->flush();

            $message = 'L\'opération et le fichier ont été supprimés avec succès.';
            return new JsonResponse(['message' => $message]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la suppression du fichier.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



}