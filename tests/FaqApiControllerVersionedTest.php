<?php

namespace Restruct\FAQ\Tests;

use Restruct\FAQ\Model\FaqQuestion;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Versioned\Versioned;

/**
 * The view-counting endpoint on a site that adds Versioned (with stages) to FaqQuestion. The
 * module does not, but a project may; the front end then reads the Live table, so a count kept
 * only in the draft table would never move for visitors.
 */
class FaqApiControllerVersionedTest extends FunctionalTest
{
    protected static $fixture_file = 'FaqTest.yml';

    protected static $required_extensions = [
        FaqQuestion::class => [Versioned::class],
    ];

    private const TOKEN = 'faq-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        # As in FaqApiControllerTest: tokens on, and a known one in the test session.
        SecurityToken::enable();
        $this->session()->set(SecurityToken::inst()->getName(), self::TOKEN);
    }

    protected function tearDown(): void
    {
        SecurityToken::enable();
        parent::tearDown();
    }

    private function countIn(string $stage, int $id): int
    {
        $question = FaqQuestion::singleton();
        $table = $question->stageTable(DataObject::getSchema()->tableName(FaqQuestion::class), $stage);

        return (int) SQLSelect::create('"ViewCount"', sprintf('"%s"', $table), ['"ID"' => $id])
            ->execute()
            ->value();
    }

    public function testAViewOfAPublishedQuestionCountsOnBothStages(): void
    {
        $question = $this->objFromFixture(FaqQuestion::class, 'q_returns');
        $question->publishSingle();

        $response = $this->post('faq-api/incrementView', [
            'faqId' => $question->ID,
            SecurityToken::inst()->getName() => self::TOKEN,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, json_decode($response->getBody(), true)['viewCount']);
        // Live is what visitors' pages read; draft is what the CMS admin lists.
        $this->assertSame(1, $this->countIn(Versioned::LIVE, $question->ID));
        $this->assertSame(1, $this->countIn(Versioned::DRAFT, $question->ID));
    }
}
