<?php

/**
 * @file classes/bodyText/Repository.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to find and manage body tesxt files.
 */

namespace PKP\bodyText;

use APP\core\Application;
use APP\facades\Repo;
use APP\publication\Publication;
use APP\submission\Submission;
use DOMDocument;
use Exception;
use PhpOffice\PhpWord\IOFactory;
use PKP\config\Config;
use PKP\db\DAORegistry;
use PKP\file\FileManager;
use PKP\file\TemporaryFileManager;
use PKP\submissionFile\SubmissionFile;
use Throwable;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Image;

class Repository
{
    /**
     * Summarize the BodyTextFile along with the body text content
     */
    public function summarize(BodyTextFile $bodyTextFile): array
    {
        $fileProps = [];
        if ($bodyTextFile->submissionFile) {
            $fileProps = Repo::submissionFile()
                ->getSchemaMap()
                ->summarize($bodyTextFile->submissionFile, $bodyTextFile->genres);
        }

        if ($bodyTextFile->bodyTextContent) {
            $fileProps['bodyTextContent'] = $bodyTextFile->bodyTextContent;
        }

        if ($bodyTextFile->loadingContentError) {
            $fileProps['loadingContentError'] = $bodyTextFile->loadingContentError;
        }

        if ($bodyTextFile->sourceFile) {
            $fileProps['sourceFile'] = Repo::submissionFile()
                ->getSchemaMap()
                ->summarize($bodyTextFile->sourceFile, $bodyTextFile->genres);
        }

        return $fileProps;
    }

    public function createHtmlFileFromDocx(
        SubmissionFile $submissionFile,
        int $publicationId
    ): SubmissionFile {
        // Load the original file
        $originalPath = Config::getVar('files', 'files_dir') . '/' . $submissionFile->getData('path');

        // Convert .docx to HTML
        $phpWord = IOFactory::load($originalPath);
        $htmlTempPath = tempnam(sys_get_temp_dir(), 'html_') . '.html';

        IOFactory::createWriter($phpWord, 'HTML')->save($htmlTempPath);
        $htmlContent = file_get_contents($htmlTempPath);
        if ($htmlContent === false) {
            throw new \Exception('Could not read generated HTML content');
        }

        // Write new file to final OJS file directory
        $submission = Repo::submission()->get($submissionFile->getData('submissionId'));
        $contextId = $submission->getData('contextId');

        $submissionDir = Repo::submissionFile()->getSubmissionDir($contextId, $submission->getId());
        $finalPath = $submissionDir . '/' . uniqid() . '.html';

        // Move the file using FileManager (with file ID tracking)
        $fileId = app()->get('file')->add($htmlTempPath, $finalPath);

        // Set up metadata
        $context = Application::get()->getRequest()->getContext();
        $user = Application::get()->getRequest()->getUser();
        $primaryLocale = $context->getPrimaryLocale();
        $allowedLocales = $context->getData('supportedSubmissionLocales');

        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genres = $genreDao->getEnabledByContextId($context->getId());
        $genre = $genres->next();

        $params = [
            'fileId' => $fileId,
            'submissionId' => $submission->getId(),
            'uploaderUserId' => $user->getId(),
            'fileStage' => SubmissionFile::SUBMISSION_FILE_BODY_TEXT,
            'assocType' => Application::ASSOC_TYPE_PUBLICATION,
            'assocId' => $publicationId,
            'genreId' => $genre ? $genre->getId() : null,
            'name' => [$primaryLocale => 'converted-body-text.html'],
        ];

        // Validate and persist
        $errors = Repo::submissionFile()->validate(null, $params, $allowedLocales, $primaryLocale);
        if (!empty($errors)) {
            app()->get('file')->delete($fileId);
            throw new \Exception('Validation failed: ' . implode(', ', $errors));
        }

        $newSubmissionFile = Repo::submissionFile()->newDataObject($params);
        $newFileId = Repo::submissionFile()->add($newSubmissionFile);

        // Clean up temp file
        unlink($htmlTempPath);

        return Repo::submissionFile()->get($newFileId);
    }

    function isDocxFile(SubmissionFile $file): bool 
    {
        $filePath = $file->getData('path'); // e.g., '45-1-1234.docx'
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return strtolower($extension) === 'docx' || strtolower($extension) === 'odt';
    }

    function convertDocxSubmissionFileToHtml(SubmissionFile $submissionFile): string
    {
        // Check file extension
        $filePath = $submissionFile->getData('path');
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // if ($extension !== 'docx') {
        //     throw new Exception('Submission file is not a .docx document.');
        // }

        // Get full path on disk
        $fullFilePath = Config::getVar('files', 'files_dir') . '/' . $filePath;

        if (!file_exists($fullFilePath)) {
            throw new Exception("File not found at $fullFilePath");
        }

        try {
            // Load the DOCX file
            // $phpWord = IOFactory::load($fullFilePath, 'Word2007');

            // // Save to temporary HTML
            // $htmlPath = tempnam(sys_get_temp_dir(), 'docx_html_') . '.html';
            // IOFactory::createWriter($phpWord, 'HTML')->save($htmlPath);

            // $html = file_get_contents($htmlPath);

            // // delete the temp file
            // unlink($htmlPath);

            // // Extract inner body content
            // // $dom = new DOMDocument();
            // // libxml_use_internal_errors(true);
            // // $dom->loadHTML($html);
            // // libxml_clear_errors();

            // // $body = $dom->getElementsByTagName('body')->item(0);
            // // $cleanHtml = '';
            // // foreach ($body->childNodes as $child) {
            // //     $cleanHtml .= $dom->saveHTML($child);
            // // }

            // return $html;

            // $phpWord = IOFactory::load($fullFilePath, 'Word2007');

            // // Save as HTML into a variable
            // $xmlWriter = IOFactory::createWriter($phpWord, 'HTML');
            // ob_start();
            // $xmlWriter->save('php://output');
            // $htmlContent = ob_get_clean();

            // return $htmlContent;
            $phpWord = IOFactory::load($fullFilePath);
            // Save to temporary HTML
            $htmlPath = tempnam(sys_get_temp_dir(), 'docx_html_') . '.html';
            IOFactory::createWriter($phpWord, 'HTML')->save($htmlPath);

            $html = file_get_contents($htmlPath);

            // foreach ($phpWord->getSections() as $secIndex => $section) {
            //     $html .= "<section id='section{$secIndex}'>\n";

            //     $elements = $section->getElements();
            //     $listBuffer = [];

            //     foreach ($elements as $el) {
            //         // --- Handle Lists ---
            //         if ($el instanceof ListItem) {
            //             $listBuffer[] = $el;
            //             continue;
            //         } else {
            //             // flush previous list if needed
            //             if (!empty($listBuffer)) {
            //                 $html .= $this->renderListBuffer($listBuffer);
            //                 $listBuffer = [];
            //             }
            //         }

            //         // --- TextRun (styled paragraph) ---
            //         if ($el instanceof TextRun) {
            //             $html .= '<p>';
            //             foreach ($el->getElements() as $child) {
            //                 $html .= $this->renderStyledText($child);
            //             }
            //             $html .= "</p>\n";
            //         }

            //         // --- Text (standalone) ---
            //         elseif ($el instanceof Text) {
            //             $html .= '<p>' . $this->renderStyledText($el) . "</p>\n";
            //         }

            //         // --- Table ---
            //         elseif ($el instanceof Table) {
            //             $html .= "<table border='1' cellpadding='5' cellspacing='0'>\n";
            //             foreach ($el->getRows() as $row) {
            //                 $html .= "<tr>\n";
            //                 foreach ($row->getCells() as $cell) {
            //                     $html .= "<td>";
            //                     foreach ($cell->getElements() as $celEl) {
            //                         if ($celEl instanceof TextRun) {
            //                             foreach ($celEl->getElements() as $t) {
            //                                 $html .= $this->renderStyledText($t);
            //                             }
            //                         } elseif ($celEl instanceof Text) {
            //                             $html .= $this->renderStyledText($celEl);
            //                         }
            //                     }
            //                     $html .= "</td>\n";
            //                 }
            //                 $html .= "</tr>\n";
            //             }
            //             $html .= "</table>\n";
            //         }

            //         // --- Image ---
            //         elseif ($el instanceof Image) {
            //             $src = $el->getSource(); // path to image file
            //             $html .= '<p><img src="' . htmlspecialchars($src) . '" alt="Image" /></p>' . "\n";
            //         }
            //     }

            //     // Flush final list if needed
            //     if (!empty($listBuffer)) {
            //         $html .= $this->renderListBuffer($listBuffer);
            //     }

            //     $html .= "</section>\n";
            // }

            return $html;
        } catch (Throwable $e) {
            throw new Exception('Error converting DOCX to HTML: ' . $e->getMessage());
        }
    }

    function renderStyledText($textEl): string 
    {
        if (!($textEl instanceof Text)) return '';

        $text = htmlspecialchars($textEl->getText());
        $style = $textEl->getFontStyle();

        if ($style instanceof Font) {
            if ($style->isBold()) $text = "<strong>$text</strong>";
            if ($style->isItalic()) $text = "<em>$text</em>";
            if ($style->getUnderline()) {
                $text = "<span style=\"text-decoration:underline;\">$text</span>";
            }
            if ($color = $style->getColor()) {
                $text = "<span style=\"color:#$color\">$text</span>";
            }
        }

        return $text;
    }

    function renderListBuffer(array $items): string 
    {
        if (empty($items)) return '';

        // Assume first item defines list type
        $isNumbered = strtolower($items[0]->getStyle()) === 'number';
        $listTag = $isNumbered ? 'ol' : 'ul';

        $html = "<$listTag>\n";
        foreach ($items as $li) {
            $html .= "<li>" . htmlspecialchars($li->getText()) . "</li>\n";
        }
        $html .= "</$listTag>\n";

        return $html;
    }

    function createHtmlSubmissionFile(
        Submission $submission,
        Publication $publication,
        string $tmpFilePath,
        string $originalFileName = 'converted.html',
        int $submissionFileId
    ): SubmissionFile {
        $contextId = $submission->getData('contextId');
        $submissionId = $submission->getId();
        $user = Application::get()->getRequest()->getUser();
        $context = Application::get()->getRequest()->getContext();

        $fileManager = new FileManager();
        $extension = $fileManager->parseFileExtension($originalFileName);

        $submissionDir = Repo::submissionFile()
            ->getSubmissionDir($contextId, $submissionId);

        $storedPath = $submissionDir . '/' . uniqid() . '.' . $extension;

        // Move the file
        $fileId = app()->get('file')->add($tmpFilePath, $storedPath);

        // Determine genre
        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genres = $genreDao->getEnabledByContextId($contextId)->toArray();
        $genreId = $genres[0]->getId(); // Pick the first available genre

        // Set up the file metadata
        $primaryLocale = $context->getPrimaryLocale();
        $params = [
            'fileId' => $fileId,
            'submissionId' => $submissionId,
            'uploaderUserId' => $user->getId(),
            'fileStage' => SubmissionFile::SUBMISSION_FILE_BODY_TEXT,
            'assocType' => Application::ASSOC_TYPE_PUBLICATION,
            'assocId' => $publication->getId(),
            'genreId' => $genreId,
            'name' => [$primaryLocale => $originalFileName],
            'sourceSubmissionFileId' => $submissionFileId
        ];

        // Validate and create file
        $allowedLocales = $context->getData('supportedSubmissionLocales');
        $errors = Repo::submissionFile()->validate(null, $params, $allowedLocales, $primaryLocale);

        if (!empty($errors)) {
            app()->get('file')->delete($fileId);
            throw new Exception('Validation failed: ' . implode(', ', $errors));
        }

        $submissionFile = Repo::submissionFile()->newDataObject($params);
        Repo::submissionFile()->add($submissionFile);

        return $submissionFile;
    }

    /**
     * Creates the default JATS XML Content from the given submission/publication metadata
     *
     * @throws \PKP\jats\exceptions\UnableToCreateJATSContentException If the default JATS creation fails
     */
    public function createBodyTextFromDocx(int $publicationId, int $submissionFileId, ?int $submissionId = null): string
    {
        $publication = Repo::publication()->get($publicationId, $submissionId);
        $submission = Repo::submission()->get($publication->getData('submissionId'));

        $context = app()->get('context')->get($submission->getData('contextId'));
        $section = $submission->getSectionId() ? Repo::section()->get($submission->getSectionId()) : null;

        $issue = null;
        if ($publication->getData('issueId')) {
            $issue = Repo::issue()->get($publication->getData('issueId'));
        }

        try {
            $submissionFile = Repo::submissionFile()->get($submissionFileId, $submissionId);
            $fileName = Config::getVar('files', 'files_dir') .  '/'  .  $submissionFile->getData('path') .  '';

            $phpWord = IOFactory::load($fileName);

            $htmlPath = sys_get_temp_dir() .  '/temp_doc.html';

            IOFactory::createWriter($phpWord, 'HTML')->save($htmlPath);

            $html = file_get_contents($htmlPath);
        } catch (Throwable $e) {
            throw new Exception($e);
        }

        return $html;
    }

    /**
     * Base function that will add a new JATS file
     */
    public function addBodyTextFile(
        int $submissionFileId,
        int $publicationId,
        ?int $submissionId
    ): BodyTextFile {
        $publication = Repo::publication()->get($publicationId, $submissionId);
        $submission = Repo::submission()->get($publication->getData('submissionId'));

        $context = Application::get()->getRequest()->getContext();
        $user = Application::get()->getRequest()->getUser();

        // If no genre has been set and there is only one genre possible, set it automatically
        /** @var GenreDAO */
        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genres = $genreDao->getEnabledByContextId($context->getId());

        $existingBodyTextFile = $this->getBodyTextFile($publicationId, $submissionId, $genres->toArray());
        if ($existingBodyTextFile->submissionFile) {
            throw new Exception('An Edited file already exists');
        }

        $submissionFile = Repo::submissionFile()->get($submissionFileId, $submission->getId());
        if (!$submissionFile) {
            throw new Exception('Submission file not found. Wrong param');
        }

        if ($this->isDocxFile($submissionFile)) {
            $html = $this->convertDocxSubmissionFileToHtml($submissionFile); // from previous step
            $tmpFilePath = tempnam(sys_get_temp_dir(), 'html_') . '.html';
            file_put_contents($tmpFilePath, $html);

            $newFile = $this->createHtmlSubmissionFile($submission, $publication, $tmpFilePath, 'converted_from_docx.html', $submissionFileId);
        }

        $bodyTextFile = Repo::bodyText()
            ->getBodyTextFile($publication->getId(), $submission->getId(), $genres->toArray());

        return $bodyTextFile;
    }

    /**
     * Returns the SubmissionFile, if any, that corresponds to the body text contents of the given submission/publication
     */
    public function getBodyTextFile(int $publicationId, ?int $submissionId = null, array $genres): ?BodyTextFile
    {
        $submissionFileQuery = Repo::submissionFile()
            ->getCollector()
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_BODY_TEXT])
            ->filterByAssoc(Application::ASSOC_TYPE_PUBLICATION, [$publicationId]);

        if ($submissionId) {
            $submissionFileQuery = $submissionFileQuery->filterBySubmissionIds([$submissionId]);
        }

        $submissionFile = $submissionFileQuery
            ->getMany()
            ->first();

        // $submissionFile = Repo::submissionFile()->get($submissionFileId, $submissionId);
        // $fileName = Config::getVar('files', 'files_dir') .  '/'  .  $submissionFile->getData('path') .  '';

        return new BodyTextFile(
            $publicationId,
            $submissionId,
            $submissionFile,
            $genres
        );
    }

    /**
     * Base function that will add a new body text file
     */
    public function setBodyText(
        string $bodyText,
        int $publicationId,
        ?int $submissionId = null,
        int $type = SubmissionFile::SUBMISSION_FILE_BODY_TEXT,
        array $params = []
    ): BodyTextFile {
        $publication = Repo::publication()->get($publicationId, $submissionId);
        $submission = Repo::submission()->get($publication->getData('submissionId'));

        $context = Application::get()->getRequest()->getContext();
        $user = Application::get()->getRequest()->getUser();

        // If no genre has been set and there is only one genre possible, set it automatically
        /** @var GenreDAO */
        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genres = $genreDao->getEnabledByContextId($context->getId());

        $temporaryFileManager = new TemporaryFileManager();
        $temporaryFilename = tempnam($temporaryFileManager->getBasePath(), 'bodyText');
        if (!file_put_contents($temporaryFilename, $bodyText)) {
            throw new \Exception('Unable to save body text!');
        }

        $submissionDir = Repo::submissionFile()
            ->getSubmissionDir(
                $submission->getData('contextId'),
                $submission->getId()
            );

        $fileId = app()->get('file')->add(
            $temporaryFilename,
            $submissionDir . '/' . uniqid() . '.txt'
        );

        $params['fileId'] = $fileId;
        $params['submissionId'] = $submission->getId();
        $params['uploaderUserId'] = $user->getId();
        $params['fileStage'] = $type;

        $primaryLocale = $context->getPrimaryLocale();
        $allowedLocales = $context->getData('supportedSubmissionLocales');
        $params['name'] = [$primaryLocale => 'bodyText'];

        if (empty($params['genreId'])) {

            [$firstGenre, $secondGenre] = [$genres->next(), $genres->next()];
            if ($firstGenre && !$secondGenre) {
                $params['genreId'] = $firstGenre->getId();
            }
        }

        $params['assocType'] = Application::ASSOC_TYPE_PUBLICATION;
        $params['assocId'] = $publication->getId();

        $errors = Repo::submissionFile()
            ->validate(
                null,
                $params,
                $allowedLocales,
                $primaryLocale
            );

        if (!empty($errors)) {
            app()->get('file')->delete($fileId);
            throw new Exception(print_r($errors, true));
        }

        $submissionFile = Repo::submissionFile()
            ->newDataObject($params);

        $submissionFileId = Repo::submissionFile()
            ->add($submissionFile);

        $bodyTextFile = Repo::bodyText()
            ->getBodyTextFile($publication->getId(), $submission->getId(), $genres->toArray());

        return $bodyTextFile;
    }

    /**
     * Get all valid file stages
     *
     * Valid file stages should be passed through
     * the hook SubmissionFile::fileStages.
     */
    public function getFileStages(): array
    {
        return [SubmissionFile::SUBMISSION_FILE_BODY_TEXT];
    }
}
