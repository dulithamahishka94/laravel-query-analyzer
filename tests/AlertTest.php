<?php

namespace Coderflex\QueryLens\Tests;

use Coderflex\QueryLens\Models\Alert;
use Orchestra\Testbench\TestCase;
use Coderflex\QueryLens\QueryLensServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [QueryLensServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('query-lens.web_ui.enabled', true);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /** @test */
    public function it_can_create_an_alert()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware();

        $data = [
            'name' => 'Slow Query Alert',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => [
                'threshold' => 1.0,
            ],
            'channels' => ['log', 'mail'],
            'cooldown_minutes' => 5,
        ];

        $response = $this->postJson(route('query-lens.api.v2.alerts.store'), $data);

        $response->assertStatus(201)
            ->assertJsonPath('alert.name', 'Slow Query Alert');

        $this->assertDatabaseHas('query_lens_alerts', [
            'name' => 'Slow Query Alert',
            'type' => 'slow_query',
        ]);
    }

    /** @test */
    public function it_validates_alert_creation()
    {
        $response = $this->postJson(route('query-lens.api.v2.alerts.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'conditions', 'channels']);
    }

    /** @test */
    public function it_validates_channel_types()
    {
        $data = [
            'name' => 'Invalid Channel Alert',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => ['threshold' => 1.0],
            'channels' => ['invalid_channel'],
        ];

        $response = $this->postJson(route('query-lens.api.v2.alerts.store'), $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channels.0']);
    }

    /** @test */
    public function it_sends_email_notification()
    {
        \Illuminate\Support\Facades\Mail::fake();
        
        config()->set('query-lens.alerts.enabled', true);
        config()->set('query-lens.alerts.mail_to', 'test@example.com');

        $alert = Alert::create([
            'name' => 'Email Alert',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => ['threshold' => 1.0],
            'channels' => ['mail'],
            'cooldown_minutes' => 0,
        ]);

        $query = new \Coderflex\QueryLens\Models\AnalyzedQuery();
        $query->time = 2.0;
        $query->sql = 'SELECT * FROM users';
        $query->id = 'test-query-id';

        $service = new \Coderflex\QueryLens\Services\AlertService();
        $service->checkAlerts($query);

        \Illuminate\Support\Facades\Mail::assertSent(\Coderflex\QueryLens\Mail\AlertTriggered::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    /** @test */
    public function it_sends_slack_notification()
    {
        \Illuminate\Support\Facades\Http::fake();
        
        config()->set('query-lens.alerts.enabled', true);
        config()->set('query-lens.alerts.slack_webhook', 'https://hooks.slack.com/services/test');

        $alert = Alert::create([
            'name' => 'Slack Alert',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => ['threshold' => 1.0],
            'channels' => ['slack'],
            'cooldown_minutes' => 0,
        ]);

        $query = new \Coderflex\QueryLens\Models\AnalyzedQuery();
        $query->time = 2.0;
        $query->sql = 'SELECT * FROM users';
        $query->id = 'test-query-id';

        $service = new \Coderflex\QueryLens\Services\AlertService();
        $service->checkAlerts($query);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return $request->url() == 'https://hooks.slack.com/services/test' &&
                   $request['text'] === 'Query Analyzer Alert: Slack Alert';
        });
    }
}
