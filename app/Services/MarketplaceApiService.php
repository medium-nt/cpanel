<?php

namespace App\Services;

use App\Models\Sku;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class MarketplaceApiService
{
    public static function getItems($cursor = 0): object|false|null
    {
        $response = Http::accept('application/json')
        ->withOptions(['verify' => false])
        ->withHeaders(['Authorization' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6IjIwMjUwNDE3djEiLCJ0eXAiOiJKV1QifQ.eyJlbnQiOjEsImV4cCI6MTc2MjI4NTk4MSwiaWQiOiIwMTk2YTQ5NS03YWQyLTdhYTUtODE4ZC04MDk3NDdkOTViNjMiLCJpaWQiOjQ0NDYxNDg3LCJvaWQiOjgzMDE0MSwicyI6MTA3Mzc0NDkxNCwic2lkIjoiMzhkZGE1MzMtNmNlMy00Yzk5LTkyNjEtMDg0YzNkZDg5MDE1IiwidCI6ZmFsc2UsInVpZCI6NDQ0NjE0ODd9.8foQ5Lb0aIs0A-GJ7tyCuEvM687mN8DzSjVPf-JKooRf_tVKZMbFM9ZZVBdDAhV6Fhd9_pMZ1c91-BphreFxaA',])
        ->post('https://content-api.wildberries.ru/content/v2/get/cards/list', $cursor);

        if(!$response->ok()) {
            return false;
        }

        return $response->object();
    }

    public static function getAllItems(): array
    {
        $productsArray = [];

        $limit = 100;
        $cursor = [
            "settings" => [
                "cursor" => [
                    "limit" => $limit
                ],
                "filter" => [
                    "withPhoto" => -1
                ]
            ]
        ];

        do {
            $items = MarketplaceApiService::getItems($cursor);

            if(!$items) {
                return $productsArray;
            }

            foreach ($items->cards as $product) {
                $array = [
                    'imtID' => $product->imtID,
                    'nmID' => $product->nmID,
                    'title' => $product->title,
                    'skus' => [],
                ];

                foreach ($product->sizes as $size) {
                    $array['skus'][] = $size->skus[0] ?? '';
                }

                $productsArray[] = $array;
            }


            if (isset($items->cursor->total) && $items->cursor->total >= $limit) {
                $cursor["settings"]["cursor"]["updatedAt"] = $items->cursor->updatedAt;
                $cursor["settings"]["cursor"]["nmID"] = $items->cursor->nmID;
            } else {
                break;
            }
        } while (true);

        return $productsArray;
    }

    public static function getNotFoundSkus($allItems): array
    {
        $notFoundSkus = [];
        foreach ($allItems as $item) {
            $skuz = $item['skus'][0];

            if (!Sku::query()->where('sku', $skuz)->first()) {
                $notFoundSkus[] = $item;
            }
        }

        return $notFoundSkus;
    }

}
