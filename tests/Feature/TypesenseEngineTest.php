<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Shopper\Core\Models\Channel;
use Shopper\Search\Facades\Search;

it('filters typesense searches on the public id of the channel', function (): void {
    $channel = Channel::factory()->create();

    $searches = (fn (): array => $this->buildSearch([]))
        ->call(Search::driver('typesense')->channel($channel));

    expect($searches)->each->toHaveKey('filter_by', 'channels:=`'.$channel->public_id.'`');
});

it('returns no results while the typesense collection does not exist', function (): void {
    config([
        'scout.driver' => 'typesense',
        'scout.typesense.client-settings.client' => new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], (string) json_encode(['results' => [['code' => 404, 'error' => 'Collection not found']]])),
        ]))]),
    ]);

    expect(Search::driver('typesense')->query('shirt')->get()->count)->toBe(0);
});
