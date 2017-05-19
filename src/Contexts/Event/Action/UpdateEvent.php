<?php

namespace PhotoContainer\PhotoContainer\Contexts\Event\Action;


use PhotoContainer\PhotoContainer\Contexts\Event\Domain\EventRepository;
use PhotoContainer\PhotoContainer\Contexts\Event\Response\EventUpdateResponse;
use PhotoContainer\PhotoContainer\Infrastructure\Web\DomainExceptionResponse;

class UpdateEvent
{
    protected $repository;

    public function __construct(EventRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $id, array $data)
    {
        try {
            $event = $this->repository->find($id);
            $this->repository->update($id, $data, $event);

            return new EventUpdateResponse($event);
        } catch (\Exception $e) {
            return new DomainExceptionResponse($e->getMessage());
        }
    }
}
