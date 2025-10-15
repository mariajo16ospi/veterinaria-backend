<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Storage;

class FirebaseService
{
    protected $database;
    protected $auth;
    protected $storage;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config('/storage/app/firebase/firebase-credentials.json'))
            ->withDatabaseUri(config('https://veterinaria-mym2025-default-rtdb.firebaseio.com'));

        $this->database = $factory->createDatabase();
        $this->auth = $factory->createAuth();
        $this->storage = $factory->createStorage();
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }

    public function getStorage(): Storage
    {
        return $this->storage;
    }

    public function create(string $collection, array $data)
    {
        $newRef = $this->database->getReference($collection)->push($data);
        return [
            'id' => $newRef->getKey(),
            'data' => $data
        ];
    }

    public function getAll(string $collection)
    {
        $snapshot = $this->database->getReference($collection)->getSnapshot();
        return $snapshot->getValue();
    }

    public function getById(string $collection, string $id)
    {
        $snapshot = $this->database->getReference($collection . '/' . $id)->getSnapshot();
        return $snapshot->getValue();
    }

    public function update(string $collection, string $id, array $data)
    {
        $this->database->getReference($collection . '/' . $id)->update($data);
        return $this->getById($collection, $id);
    }

    public function delete(string $collection, string $id)
    {
        $this->database->getReference($collection . '/' . $id)->remove();
        return true;
    }

    public function query(string $collection, string $key, string $operator, $value)
    {
        $reference = $this->database->getReference($collection);

        switch ($operator) {
            case '==':
                $snapshot = $reference->orderByChild($key)->equalTo($value)->getSnapshot();
                break;
            case '>':
                $snapshot = $reference->orderByChild($key)->startAt($value)->getSnapshot();
                break;
            case '<':
                $snapshot = $reference->orderByChild($key)->endAt($value)->getSnapshot();
                break;
            default:
                $snapshot = $reference->getSnapshot();
        }

        return $snapshot->getValue();
    }
}
