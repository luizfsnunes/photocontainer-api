<?php

namespace PhotoContainer\PhotoContainer\Contexts\Event\Persistence;

use PhotoContainer\PhotoContainer\Contexts\Event\Domain\Favorite;
use PhotoContainer\PhotoContainer\Contexts\Event\Domain\FavoriteRepository;
use PhotoContainer\PhotoContainer\Contexts\Event\Domain\Publisher;
use PhotoContainer\PhotoContainer\Infrastructure\Exception\PersistenceException;
use PhotoContainer\PhotoContainer\Infrastructure\Persistence\Eloquent\EventFavorite;


class EloquentFavoriteRepository implements FavoriteRepository
{
    public function createFavorite(Favorite $favorite): Favorite
    {
        try {
            $eventFavorite = new EventFavorite();
            $eventFavorite->user_id = $favorite->getPublisher()->getId();
            $eventFavorite->event_id = $favorite->getEventId();
            $eventFavorite->save();

            $favorite->changeTotalLikes(EventFavorite::where('event_id', $favorite->getEventId())->count());
            $favorite->changeId($eventFavorite->id);

            return $favorite;
        } catch (\Exception $e) {
            throw new PersistenceException("Erro na criação do favorito!", $e->getMessage());
        }
    }

    public function removeFavorite(Favorite $favorite): Favorite
    {
        try {
            EventFavorite::where('event_id', $favorite->getEventId())
                ->where('user_id', $favorite->getPublisher()->getId())
                ->delete();

            $favorite->changeTotalLikes(EventFavorite::where('event_id', $favorite->getEventId())->count());

            return $favorite;
        } catch (\Exception $e) {
            throw new PersistenceException($e->getMessage());
        }
    }

    public function findFavorite(Favorite $favorite): ?Favorite
    {
        if ($favorite->getId()) {
            $data = EventFavorite::find($favorite->getId());
            $favorite->changeEventId($data['event_id']);
            $favorite->changePublisher(new Publisher($data['user_id'], null, null));

            return $favorite;
        }

        $data = EventFavorite::where([
            'event_id' => $favorite->getEventId(),
            'user_id' => $favorite->getPublisher()->getId(),
        ])->get('id')->first()->toArray();

        if ($data) {
            $favorite->changeId($data['id']);
            return $favorite;
        }

        return null;
    }
}