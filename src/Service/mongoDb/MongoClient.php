<?php
namespace App\Service\mongoDb;

use IteratorIterator;
use MongoDB\Client as Mongo;
use MongoDB\Collection;
use MongoDB\Operation\CreateCollection;
use MongoDB\Operation\DeleteOne;
use MongoDB\Operation\FindOne;
use MongoDB\Operation\InsertOne;

class MongoClient
{
    public function __construct(
    ) {
    }

    public function MakeClient(): Mongo
    {
        $client = new Mongo("mongodb://root:ALBER596tui&&r365@mongo:27017");
        return $client;
    }

    public function GetCollection(string $table): Collection
    {
        $client = $this->MakeClient();
        return $client->{"GVRlight"}->$table;
    }


    public function GetVehData(string $vid): array|null
    {
        $collection = $this->GetCollection('Vehicule');

        $data = $collection->findOne(['vid' => $vid], [
            'typeMap' => [
                'document' => 'array',
                'root' => 'array'
            ]
        ]);

        return $data;
    }

    public function SetVehData(string $vid, string $document, string $col, string $line, string|null $value): array
    {
        $data = $this->GetVehData($vid);
        //dd($data);

        if (!$data) {
            $data = [
                'vid' => $vid,
                'data' => [
                    "Equippements" => null,
                    "Accessoires" => null,
                    "Options" => null,
                ]
            ];
        }

        $collection = $this->GetCollection('Vehicule');

        $collection->DeleteOne(['vid' => $vid]);

        $data['data'][$document][$col][$line] = $value;

        $collection->InsertOne($data);

        return $data;
    }


}