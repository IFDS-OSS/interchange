<?php

namespace Workbench\App\Console\Commands;

use Ifds\HttpAdapter\Facades\HttpAdapter;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class SampleApiCommand extends Command
{
    protected $signature = 'http-adapter:sample {post=1 : The JSONPlaceholder post id to fetch}';

    protected $description = 'Call the JSONPlaceholder sample API through the "sample" driver (live network call).';

    public function handle(): int
    {
        $id = (int) $this->argument('post');

        try {
            $post = HttpAdapter::driver('sample')
                ->showPost()
                ->withPathParam('id', $id)
                ->send()
                ->json();
        } catch (ConnectionException $e) {
            $this->error("Could not reach the sample API: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("GET /posts/{$id}");
        $this->line('  title: '.($post['title'] ?? '—'));

        $comments = HttpAdapter::driver('sample')->commentsFor($id);
        $this->info('GET /posts/'.$id.'/comments');
        $this->line('  '.count($comments).' comment(s)');

        // The same endpoint served from its 'mock' entry — no network call.
        // Resolved drivers are memoised by Illuminate\Support\Manager, so the
        // cached instance has to be dropped for the new config to take effect.
        config()->set('http-adapter.drivers.sample.mock_enabled', true);
        HttpAdapter::forgetDrivers();

        $created = HttpAdapter::driver('sample')
            ->createPost(['title' => 'from the workbench'])
            ->send();

        $this->info('POST /posts (mocked)');
        $this->line('  '.json_encode($created->json()));

        return self::SUCCESS;
    }
}
