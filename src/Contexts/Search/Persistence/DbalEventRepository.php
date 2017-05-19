<?php

namespace PhotoContainer\PhotoContainer\Contexts\Search\Persistence;

use PhotoContainer\PhotoContainer\Contexts\Search\Domain\Category;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\Event;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\EventRepository;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\EventSearch;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\Photographer;
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
                foreach ($allTags as $tag) {
                    $tags[] = $tag->getId();
                }

                $where[] = 'tag_id IN ('.implode(',', $tags).')';
            }

            $publisher = $search->getPublisher();

            $table = $publisher ? 'event_search_publisher' : 'event_search';

            $where = empty($where) ? '' : " WHERE ".implode(" ", $where);

            $stmt = $this->conn->prepare("SELECT id, user_id, name, title, eventdate, category_id, category, photos, likes as total 
                      FROM {$table} {$where}
                  GROUP BY id, category_id, category
                  ORDER BY id DESC");
            $stmt->execute();
            $result = $stmt->fetchAll();

            $out = ['total' => count($result)];

            foreach ($result as $key => $item) {
                $category = new Category($item['category_id'], $item['category']);
                $photographer = new Photographer($item['user_id'], $item['name']);

                $search = new EventSearch($item['id'], $photographer, $item['title'], [$category], null);
                $search->changeEventdate($item['eventdate']);
                $search->changePhotos($item['photos']);
                $search->changeLikes($item['likes'] == null ? 0 : $item['likes']);

                if ($item->photos > 0) {
                    $sql = "SELECT filename FROM photos WHERE event_id = {$item['id']}";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute();
                    $photo = $stmt->fetch();

                    $search->changeFilename($photo['filename']);
                }

                if ($publisher) {
                    $search->changePublisher($publisher);

                    if ($item->likes > 0) {
                        $sql = "SELECT count(*) as total 
                                  FROM event_favorites 
                                 WHERE event_id = {$item['id']} AND user_id = {$publisher->getId()}";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->execute();
                        $eventFavorite = $stmt->fetch();

                        $search->changePublisherLike($eventFavorite['total'] > 0);
                    }
                }
                $out['result'][] = $search;
            }

            return $out;
        } catch (\Exception $e) {
            throw $e;
        }
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