<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Pkeys\Pkey;

/**
 * Class PkeysTesting
 * @package App\Console\Commands
 */
class PkeysTesting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pkeys:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Some example usages of Pkeys.';

    /**
     * @var \Predis\Client
     */
    protected $redisClient;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        /*
         * Change config in database.php
         */
        $this->redisClient = new \Predis\Client(config('database.redis.default'));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*
         * Helper
         */
        $this->warn('First, make a key using the helper..');
        $messagesPkey = pkey('redis.user.messages',['id'=>11]);
        $this->info('The first key>> '.$messagesPkey);

        /*
         * Facade
         */
        $this->warn('Second, make a key using the facade..');
        $countPkey = \Pkey::make('redis.users.count',['status'=>'new','day'=>Carbon::now()->toDateString()]);
        $this->info('The second key>> '.$countPkey);

        /*
         * From the IOC
         */
        $this->warn('Third, make a key using the IOC..');
        $eventKey = app()->make(Pkey::class)->make('redis.events.messages',['type'=>'new']);
        $this->info('The third key>> '.$eventKey);

        /*
         * Change the schema in the IOC singleton
         *
         * This isnt really advisable as it defeats the purpose of the central schema but serves to illustrate what is going on
         */
        $this->warn('Fourth, make a key using dynamically loaded schema..');
        $newSchema = ['schema'=>['cache'=>['user'=>'user:{id}:profile:info']]];
        app()->make(Pkey::class)->setSchema($newSchema);
        $cacheKey =  pkey('cache.user',['id'=>22]);
        $this->info('The fourth key>> '.$cacheKey);

        /*
         * Load back original schema
         */
        app()->make(Pkey::class)->setSchema(config('pkeys'));

        /*
         * Run the redis tests
         */
        $this->test_using_predis_client();
    }
    
    /**
     *
     */
    public function test_using_predis_client()
    {
        $this->warn('Demonstrate how to use with Redis');
        /*
         * Request key from the schema
         */
        $countKey = pkey('redis.users.count',[
            'status'=>'active',
            'day'=>\Carbon\Carbon::now()->toDateString()
        ]);

        /*
         * Do an incr operation on that key for examples sake
         */
        $this->redisClient->incr($countKey);
        $this->info('$countKey is 1>> '.$this->redisClient->get($countKey));


        /*
         * Do an del operation on that key for examples sake.
         */
        $this->redisClient->del($countKey);
        $this->info('$countKey is null>> '.$this->redisClient->get($countKey));

        /*
         * Do some pub/sub for examples sake
         */
        /*
         * Request channel key from the schema
         */
        $pubsubChannel = pkey('redis.user.messages',[
            'id'=>21
        ]);

        /*
         * The generated pubsub channel should be `user:21:messages`
         *
         * Use redis-cli in the terminal with following commands
         *
         * `publish user:21:messages "testing a message"`
         * `publish user:21:messages "quit_loop"`
         */
        $this->warn( 'Open redis-cli in your terminal and run:');
        $this->info( '`publish '.$pubsubChannel.' "testing this message"`');
        $this->warn( 'Or to kill the loop: ');
        $this->info( '`publish '.$pubsubChannel.' "quit_loop"`');

        $pubsub = $this->redisClient->pubSubLoop();
        $pubsub->subscribe($pubsubChannel);
        foreach ($pubsub as $message) {
            switch ($message->kind) {
                case 'subscribe':
                    $this->info(  "> Subscribed to {$message->channel}");
                    break;

                case 'message':
                    if ($message->payload == 'quit_loop') {
                        $this->info( '> Aborting pubsub loop...');
                        $pubsub->unsubscribe();
                    } else {
                        $this->info(  "> Received the following message from {$message->channel}:");
                        $this->info(  ">> \"{$message->payload}\"");
                    }
                    break;
            }
        }
    }
}
