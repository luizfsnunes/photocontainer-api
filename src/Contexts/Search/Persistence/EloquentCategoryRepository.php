<?php

namespace PhotoContainer\PhotoContainer\Contexts\Search\Persistence;

use PhotoContainer\PhotoContainer\Contexts\Search\Domain\Category;
use PhotoContainer\PhotoContainer\Contexts\Search\Domain\CategoryRepository;
use PhotoContainer\PhotoContainer\Infrastructure\Exception\PersistenceException;
use PhotoContainer\PhotoContainer\Infrastructure\Persistence\Eloquent\Category as CategoryModel;
use PhotoContainer\PhotoContainer\Infrastructure\Persistence\EloquentDatabaseProvider;

class EloquentCategoryRepository implements CategoryRepository
{
    /**
     * @var EloquentDatabaseProvider
     */
    private $conn;

    public function __construct(EloquentDatabaseProvider $conn)
    {
        $this->conn = $conn;
    }

    public function findAll(): array
    {
        try {
            $all = CategoryModel::where(['active' => true])->orderBy('order')->get();

            return $all->map(function ($item, $key) {
                return new Category($item->id, $item->description);
            })->toArray();
        } catch (\Exception $e) {
            throw new PersistenceException('Erro na busca de categorias.', $e->getMessage());
        }
    }
}
