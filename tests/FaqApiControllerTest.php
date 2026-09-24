<?php

namespace Restruct\FAQ\Tests;

use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\SecurityToken;

/**
 * The view-counting endpoint behind faq-view-tracker.js, reached through the module's own route.
 */
class FaqApiControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'FaqTest.yml';

    private const TOKEN = 'faq-test-token';

    private const URL = 'faq-api/incrementView';

    protected function setUp(): void
    {
        parent::setUp();

        // FunctionalTest disables CSRF tokens by default, which would make the token check a
        // no-op and the 403 case untestable. Enable it, and put a known token in the test
        // session the same way a rendered page would.
        SecurityToken::enable();
        $this->session()->set(SecurityToken::inst()->getName(), self::TOKEN);
    }

    protected function tearDown(): void
    {
        // FunctionalTest::tearDown() re-enables tokens anyway; restated so this class leaves the
        // global state as it found it however the parent changes.
        SecurityToken::enable();
        parent::tearDown();
    }

    private function postView($faqId, ?string $token = self::TOKEN): HTTPResponse
    {
        $data = ['faqId' => $faqId];
        if ($token !== null) {
            $data[SecurityToken::inst()->getName()] = $token;
        }

        return $this->post(self::URL, $data);
    }

    private function json(HTTPResponse $response): array
    {
        $this->assertSame('application/json', $response->getHeader('Content-Type'));

        return json_decode($response->getBody(), true);
    }

    private function viewCount(string $fixture): int
    {
        $id = $this->idFromFixture(FaqQuestion::class, $fixture);

        return (int) FaqQuestion::get()->byID($id)->ViewCount;
    }

    public function testGetIsRejected(): void
    {
        $response = $this->get(self::URL);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('Method not allowed', $this->json($response)['error']);
    }

    public function testPostWithoutTokenIsRejectedAndCountsNothing(): void
    {
        $response = $this->postView($this->idFromFixture(FaqQuestion::class, 'q_returns'), null);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(0, $this->viewCount('q_returns'));
    }

    public function testPostWithWrongTokenIsRejectedAndCountsNothing(): void
    {
        $response = $this->postView($this->idFromFixture(FaqQuestion::class, 'q_returns'), 'not-the-token');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(0, $this->viewCount('q_returns'));
    }

    public function testMissingFaqIdIsABadRequest(): void
    {
        $response = $this->postView(0);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUnknownFaqIsNotFound(): void
    {
        $response = $this->postView(999999);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testFirstViewIsCounted(): void
    {
        $response = $this->postView($this->idFromFixture(FaqQuestion::class, 'q_returns'));

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertTrue($body['success']);
        $this->assertFalse($body['alreadyCounted']);
        $this->assertSame(1, $body['viewCount']);
        $this->assertSame(1, $this->viewCount('q_returns'));
    }

    public function testRepeatViewInTheSameSessionIsNotCountedAgain(): void
    {
        $id = $this->idFromFixture(FaqQuestion::class, 'q_returns');
        $this->postView($id);

        $response = $this->postView($id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->json($response)['alreadyCounted']);
        $this->assertSame(1, $this->viewCount('q_returns'));
    }

    public function testViewsAreCountedPerQuestion(): void
    {
        $this->postView($this->idFromFixture(FaqQuestion::class, 'q_returns'));
        $this->postView($this->idFromFixture(FaqQuestion::class, 'q_payment'));

        $this->assertSame(1, $this->viewCount('q_returns'));
        $this->assertSame(1, $this->viewCount('q_payment'));
        $this->assertSame(0, $this->viewCount('q_shipping'));
    }
}
