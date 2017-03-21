<?php

namespace PhotoContainer\PhotoContainer\Contexts\Event\Response;

class EventCollectionResponse implements \JsonSerializable
{
    private $httpStatus = 200;
    private $collection;

    public function __construct(array $collection)
    {
        $this->collection = $collection;
    }

    function jsonSerialize()
    {
        $out = [];
        foreach ($this->collection as $search) {
            $out[] = [
                "id" => $search->getId(),
                "photographer" => $search->getPhotographer(),
                "title" => $search->getTitle(),
                "eventdate" => $search->getEventdate(),
                "thumb" => "user/themes/photo-container-site/_temp/photos/1.jpg",
                "_links" => [
                    "_self" => ['href' => '/events/'.$search->getId()],
                ],
            ];
        }

        return $out;
    }

    /**
     * @return int
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}