<?php declare(strict_types=1);

namespace App\Tests\acceptance;

class TermTags_Test extends AcceptanceTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    ///////////////////////
    // Tests

    /**
     * @group termtagsmoke
     */
    public function test_termtag_smoke_test(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Term Tags');
        $ctx = $this->getTermTagContext();
        $ctx->listingShouldContain('no term tags', [ 'No data available in table' ]);

        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $this->client->clickLink('Create new');
        $ctx = $this->getTermContext();
        $updates = [
            'language' => $this->spanish->getLgID(),
            'Text' => 'gato',
            'Translation' => 'cat',
            'Tags' => [ 'sometag' ]
        ];
        $ctx->updateTermForm($updates);

        $wait = function() { usleep(200 * 1000); };
        $wait();
        $this->client->request('GET', '/');
        $this->client->clickLink('Term Tags');
        $wait();
        $ctx = $this->getTermTagContext();
        $ctx->listingShouldContain('tag created', [ 'sometag; ; 1; ' ]);

        $this->client->clickLink('Create new');
        $updates = [
            'Text' => 'newtag',
            'Comment' => 'some-comment'
        ];
        $ctx->updateForm($updates);
        $wait();
        $ctx->listingShouldContain('new created', [ 'newtag; some-comment; -; ', 'sometag; ; 1; ' ]);

        $this->client->clickLink('1');  // One tag.
        $wait();
        $ctx = $this->getTermContext();
        $ctx->listingShouldContain('tagged term shown', [ '; gato; ; cat; Spanish; sometag; New (1)' ]);

        $this->client->request('GET', '/');
        $wait();
        $this->client->clickLink('Term Tags');
        $wait();
        $this->client->getMouse()->clickTo("#deltermtag1");
        $this->client->getWebDriver()->switchTo()->alert()->accept(); // accept after clicking on delete
        $this->client->switchTo()->defaultContent();
        $wait();
        $ctx = $this->getTermTagContext();
        $ctx->listingShouldContain('sometag deleted', [ 'newtag; some-comment; -; ' ]);

        $this->client->request('GET', '/');
        $wait();
        $this->client->clickLink('Terms');
        $ctx = $this->getTermContext();
        $ctx->listingShouldContain('term not deleted', [ '; gato; ; cat; Spanish; ; New (1)' ]);
    }
    
}