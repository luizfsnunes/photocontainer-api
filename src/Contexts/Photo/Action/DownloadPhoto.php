<?php

namespace PhotoContainer\PhotoContainer\Contexts\Photo\Action;

use PhotoContainer\PhotoContainer\Contexts\Photo\Domain\Download;
use PhotoContainer\PhotoContainer\Contexts\Photo\Domain\Photo;
use PhotoContainer\PhotoContainer\Contexts\Photo\Domain\PhotoRepository;
use PhotoContainer\PhotoContainer\Contexts\Photo\Email\DownloadedPhoto;
use PhotoContainer\PhotoContainer\Contexts\Photo\Response\DownloadResponse;
use PhotoContainer\PhotoContainer\Infrastructure\Email\EmailHelper;
use PhotoContainer\PhotoContainer\Infrastructure\Web\DomainExceptionResponse;

class DownloadPhoto
{
    /**
     * @var PhotoRepository
     */
    private $dbRepo;

    /**
     * @var EmailHelper
     */
    private $emailHelper;

    /**
     * DownloadPhoto constructor.
     * @param PhotoRepository $dbRepo
     * @param EmailHelper $emailHelper
     */
    public function __construct(PhotoRepository $dbRepo, EmailHelper $emailHelper)
    {
        $this->dbRepo = $dbRepo;
        $this->emailHelper = $emailHelper;
    }

    /**
     * @param int $photo_id
     * @param int $publisher_id
     * @return DownloadResponse|DomainExceptionResponse
     */
    public function handle(int $photo_id, int $publisher_id)
    {
        try {
            $photo = $this->dbRepo->find($photo_id);
            $download = new Download(null, $publisher_id, $photo);

            $this->dbRepo->download($download);

            $this->sendEmailToPhotographer($photo, $publisher_id);

            return new DownloadResponse($download);
        } catch (\Exception $e) {
            return new DomainExceptionResponse($e->getMessage());
        }
    }

    /**
     * @param Photo $photo
     * @param int $publisher_id
     */
    public function sendEmailToPhotographer(Photo $photo, int $publisher_id)
    {
        $photographer = $this->dbRepo->findPhotoOwner($photo);
        $publisher = $this->dbRepo->findPublisher($publisher_id);

        $data = [
            '{TAG}' => 'TESTE DE TAG',
            '{PUBLISHER}' => $publisher->getName()
        ];

        $email = new DownloadedPhoto(
            $data,
            ['name' => $photographer->getName(), 'email' => $photographer->getEmail()]
        );

        $this->emailHelper->send($email);
    }
}
