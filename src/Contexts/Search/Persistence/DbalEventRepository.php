<?php

namespace PhotoContainer\PhotoContainer\Contexts\Search\Persistence;

use PhotoContainer\PhotoContainer\Contexts\Search\Domain\Category;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\Event;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\EventRepository;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\EventSearch;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\Photographer;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\Tag;
use PhotoContainer\PhotoContainer\Infrastructure\Exception\PersistenceException;
use PhotoContainer\PhotoContainer\Infrastructure\Persistence\DatabaseProvider;

class DbalEventRepository implements EventRepository
{
    private $conn;

    public function __construct(DatabaseProvider $provider)
    {
        $this->conn = $provider->conn;
    }

    public function find(EventSearch $search)
    {
        try {
            $where = [];

            if ($search->getTitle()) {
                $where[] = "title like %{$search->getTitle()}%";
            }

            if ($search->getPhotographer()->getId()) {
                $where[] = "user_id = {$search->getPhotographer()->getId()}";
            }

            $allCategories = $search->getCategories();
            if ($allCategories) {
                $categories = [];
                foreach ($allCategories as $category) {
                    $categories[] = $category->getId();
                }

                $where[] = 'category_id IN ('.implode(',', $categories).')';
            }

            $allTags = $search->getTags();
            if ($allTags) {
                $tags = [];
                $tagCategory = [];
                /** @var Tag $tag */
                foreach ($allTags as $tagCategory) {
                    foreach ($tagCategory as $tag) {
                        $tags[] = "'(?=.*".$tag->getId().")'";
                    }
                    $tagCategory[] = implode('', $tags);
                }

                $where[] = 'all_tags REGEXP '.implode(' OR ', $tagCategory);
            }

            $publisher = $search->getPublisher();

            $table = $publisher ? 'event_search_publisher' : 'event_search';

            $where = empty($where) ? '' : " WHERE ".implode(" ", $where);

            $sql = "SELECT id, user_id, name, title, eventdate, category_id, category, photos, likes as total 
                      FROM {$table} {$where}
                  GROUP BY id, category_id, category
                  ORDER BY id DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            $fillData = function ($datasource) use ($publisher) {
                while ($item = $datasource->fetch()) {
                    $category = new Category($item['category_id'], $item['category']);
                    $photographer = new Photographer($item['user_id'], $item['name']);

                    $likes = $item['likes'] ?? 0;

                    $search = new EventSearch($item['id'], $photographer, $item['title'], [$category], null);
                    $search->changeEventdate($item['eventdate']);
                    $search->changePhotos($item['photos']);
                    $search->changeLikes($likes);

                    if ($item['photos'] > 0) {
                        $photo = $this->findCoverPhoto($item['id']);
                        $search->changeFilename($photo['filename']);
                    }

                    if ($publisher) {
                        $search->changePublisher($publisher);

                        if ($likes > 0) {
                            $sql = "SELECT count(*) as total 
                                  FROM event_favorites 
                                 WHERE event_id = {$item['id']} AND user_id = {$publisher->getId()}";
                            $stmt = $this->conn->prepare($sql);
                            $stmt->execute();
                            $eventFavorite = $stmt->fetch();

                            $search->changePublisherLike($eventFavorite['total'] > 0);
                        }
                    }
                    yield $search;
                }
            };

            $out = ['total' => 0, 'result' => []];
            foreach ($fillData($stmt) as $search) {
                $out['result'][] = $search;
            }
            $out['total'] = count($out['result']);

            return $out;
        } catch (\Exception $e) {
            var_dump($e->getMessage());exit;
            throw new PersistenceException('Erro na pesquisa de eventos.', $e->getMessage(), 500, $e);
        }
    }

    private function findCoverPhoto(int $event_id): array
    {
        $sql = "SELECT filename FROM photos WHERE cover = 1 AND event_id = {$event_id}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $photo = $stmt->fetch();

        if ($photo) {
            return $photo;
        }

        $sql = "SELECT filename FROM photos WHERE event_id = {$event_id} LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function findWaitingRequests(int $photographer_id): ?array
    {
        // TODO: Implement findWaitingRequests() method.
    }

    public function findEventPhotosPhotographer(int $id): Event
    {
        // TODO: Implement findEventPhotosPhotographer() method.
    }

    public function findEventPhotosPublisher(int $id, int $user_id): Event
    {
        // TODO: Implement findEventPhotosPublisher() method.
    }
}