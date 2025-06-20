<?php

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Event;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "algolia", "meilisearch", "database", "collection", "null"
    |
    */

    'driver' => env('APP_ENV') === 'local' && env('LARAVEL_SAIL') ? 'meilisearch' : env('SCOUT_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => env('SCOUT_QUEUE', false) ? [
        'connection' => 'redis',
        'queue' => 'scout',
    ] : false,

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data will only be synced
    | with your search indexes after every open database transaction has
    | been committed, thus preventing any discarded data from syncing.
    |
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => 100,
        'unsearchable' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this application's users.
    |
    | Supported engines: "algolia"
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout out of the box. Just plug
    | in your application ID and admin API key to get started searching.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Meilisearch settings. Meilisearch is an open
    | source search engine with minimal configuration. Below, you can state
    | the host and key information for your own Meilisearch installation.
    |
    | See: https://www.meilisearch.com/docs/learn/configuration/instance_options#all-instance-options
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Achievement::class => [
                'filterableAttributes' => ['id', 'title'],
                'rankingRules' => [
                    'words',
                    'typo',
                    'attribute',
                    'unlocks_total:desc',
                    'proximity',
                    'exactness',
                    'sort',
                ],
                'searchableAttributes' => ['title', 'id'],
                'sortableAttributes' => [
                    'id',
                    'title',
                    'unlocks_total',
                    'unlocks_hardcore_total',
                ],
            ],

            Comment::class => [
                'filterableAttributes' => [
                    'ArticleID',
                    'ArticleType',
                    'commentable_id',
                    'commentable_type',
                    'created_at',
                    'user_id',
                ],
                'searchableAttributes' => ['body'],
                'sortableAttributes' => ['created_at'],
            ],

            Event::class => [
                'filterableAttributes' => ['title', 'players_total'],
                'rankingRules' => [
                    'words',
                    'typo',
                    'attribute',
                    'unlocks_total:desc',
                    'proximity',
                    'exactness',
                    'sort',
                ],
                'searchableAttributes' => ['title', 'id'],
                'sortableAttributes' => ['id', 'title', 'players_total'],
            ],

            ForumTopicComment::class => [
                'filterableAttributes' => [
                    'forum_topic_id',
                    'author_id',
                    'created_at',
                ],
                'searchableAttributes' => ['body'],
                'sortableAttributes' => ['created_at'],
            ],

            Game::class => [
                'filterableAttributes' => [
                    'id',
                    'title',
                    'players_total',
                    'is_subset',
                    'has_players',
                    'is_tagged',
                    'popularity_score',
                ],

                /** @see Game::toSearchableArray() for a detailed explanation on game ranking rules */
                'rankingRules' => [
                    'words',                   // Word matching first.
                    'typo',                    // Allow small typos.
                    'is_subset:asc',           // Non-subsets rank MUCH higher than subset games.
                    'popularity_score:desc',   // Very popular games.
                    'has_players:desc',        // Games with ANY players.
                    'attribute',               // Consider attribute importance (title > search_titles > alt_titles).
                    'proximity',               // Words close together rank higher.
                    'is_tagged:asc',           // Non-tagged (original) games rank higher than games with tags.
                    'players_total:desc',      // For games within the same popularity tier, sort by exact player counts.
                    'exactness',               // Finally, consider exact matches.
                    'sort',
                ],

                'searchableAttributes' => [
                    'title',
                    'search_titles',
                    'alt_titles',
                    'id',
                ],
                'sortableAttributes' => [
                    'id',
                    'title',
                    'players_total',
                    'is_subset',
                    'has_players',
                    'is_tagged',
                    'popularity_score',
                ],
                'typoTolerance' => [
                    'enabled' => true,
                    'minWordSizeForTypos' => [
                        'oneTypo' => 4,
                        'twoTypos' => 8,
                    ],
                ],
            ],

            GameSet::class => [
                'filterableAttributes' => ['id', 'games_count', 'title'],
                'rankingRules' => [
                    'exactness',
                    'words',
                    'typo',
                    'attribute',
                    'proximity',
                    'games_count:desc',
                    'sort',
                ],
                'searchableAttributes' => ['title', 'id'],
                'sortableAttributes' => ['id', 'games_count', 'title'],
            ],

            User::class => [
                'filterableAttributes' => [
                    'display_name',
                    'username',
                ],
                'rankingRules' => [
                    'exactness',
                    'words',
                    'proximity',
                    'attribute',
                    'typo',
                    'sort',
                ],
                'searchableAttributes' => ['display_name', 'username'],
                'sortableAttributes' => [
                    'display_name',
                    'last_activity_at',
                    'username',
                ],
            ],
        ],
    ],

];
