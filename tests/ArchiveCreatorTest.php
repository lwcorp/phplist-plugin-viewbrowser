<?php

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ArchiveCreatorTest extends TestCase
{
    private $users;
    private $usermessage;
    private $listmessage;
    private $lists;
    private $daoStub;
    private $translator;

    protected function setUp(): void
    {
        global $plugins;

        $this->translator = new \phpList\plugin\Common\FrontendTranslator(
            ['language_file' => 'english.inc'],
            $plugins['ViewBrowserPlugin']->coderoot
        );

        $this->users = [
            '2f93856905d26f592c7cfefbff599a0e' => [
                'id' => 51,
                'email' => 'aaa@bbb.com',
                'uniqid' => '2f93856905d26f592c7cfefbff599a0e',
                'uuid' => 'e446db8d-7bb0-4811-a054-d2951bf4176d'
            ],
            '' => ['id' => '', 'email' => 'no email', 'uniqid' => '', 'uuid' => ''],
        ];

        $this->usermessage = [
            '2f93856905d26f592c7cfefbff599a0e' => [
                ['subject' => 'first subject',
                'messageid' => 21,
                'entered' => '2017-10-01',
                ],
                ['subject' => 'second subject',
                'messageid' => 22,
                'entered' => '2017-10-02',
                ],
            ],
        ];

        $this->listmessage = [
            1 => [
                ['subject' => 'first subject',
                'messageid' => 23,
                'entered' => '2017-10-01',
                ],
                ['subject' => 'second subject',
                'messageid' => 24,
                'entered' => '2017-10-02',
                ],
            ],
            2 => [
                ['subject' => 'first subject',
                'messageid' => 25,
                'entered' => '2017-10-01',
                ],
                ['subject' => 'second subject',
                'messageid' => 26,
                'entered' => '2017-10-02',
                ],
            ],
        ];

        $this->lists = [
            1 => ['name' => 'list 1', 'active' => 1],
            2 => ['name' => 'list 2', 'active' => 0],
        ];

        $this->daoStub = $this->getMockBuilder('phpList\plugin\ViewBrowserPlugin\DAO')
            ->disableOriginalConstructor()
            ->getMock();

        $this->daoStub->method('userByUniqid')
            ->willreturnCallback(
                function ($uniqid) {
                    return $this->users[$uniqid];
                }
            );

        $this->daoStub->method('messagesForUser')
            ->willreturnCallback(
                function ($uniqid) {
                    return $this->usermessage[$uniqid];
                }
            );
        $this->daoStub->method('totalMessagesForUser')
            ->willreturnCallback(
                function ($uniqid) {
                    return count($this->usermessage[$uniqid]);
                }
            );
        $this->daoStub->method('messagesForList')
            ->willreturnCallback(
                function ($listId) {
                    return $this->listmessage[$listId];
                }
            );
        $this->daoStub->method('totalMessagesForList')
            ->willreturnCallback(
                function ($listId) {
                    return count($this->listmessage[$listId]);
                }
            );
        $this->daoStub->method('listById')
            ->willreturnCallback(
                function ($listId) {
                    return $this->lists[$listId];
                }
            );
    }

    public static function createsArchiveDataProvider()
    {
        $data = [
            'contains all messages' => [
                '2f93856905d26f592c7cfefbff599a0e',
                ['first subject', 'second subject']
            ],
            'contains date' => [
                '2f93856905d26f592c7cfefbff599a0e',
                ['1 Oct 2017']
            ],
            'contains view in browser url' => [
                '2f93856905d26f592c7cfefbff599a0e',
                [
                'pi=ViewBrowserPlugin&amp;p=view&amp;m=21&amp;uid=2f93856905d26f592c7cfefbff599a0e',
                'pi=ViewBrowserPlugin&amp;p=view&amp;m=22&amp;uid=2f93856905d26f592c7cfefbff599a0e'
                ]
            ],
        ];

        return $data;
    }

    #[DataProvider('createsArchiveDataProvider')]
    public function testCreatesArchive($uniqid, $expected, $unexpected = array())
    {
        $archive = new phpList\plugin\ViewBrowserPlugin\ArchiveCreator($this->daoStub, $this->translator);
        $result = $archive->createSubscriberArchive($uniqid);

        foreach ($expected as $e) {
            $this->assertStringContainsString($e, $result);
        }

        foreach ($unexpected as $e) {
            $this->assertNotContains($e, $result);
        }
    }

    public static function allowAccessDataProvider()
    {
        $data = [
            'createsArchivePublicList' => [
                '',
                1,
                'first subject',
            ],
            'notAllowArchivePrivateList' => [
                '',
                2,
                'Not allowed to view campaigns for list 2',
            ],
            'createsArchivePrivateList' => [
                '2 3',
                2,
                '26',
            ],
        ];

        return $data;
    }

    #[DataProvider('allowAccessDataProvider')]
    public function testAllowAccessToArchive($allowed, $listId, $expected)
    {
        global $phplist_config;

        $phplist_config['viewbrowser_anonymous'] = true;
        $phplist_config['viewbrowser_allowed_lists'] = $allowed;

        $archive = new phpList\plugin\ViewBrowserPlugin\ArchiveCreator($this->daoStub, $this->translator);
        $result = $archive->createListArchive($listId);

        $this->assertStringContainsString($expected, $result);
    }

    public function testDateIsTranslated()
    {
        global $phplist_config, $plugins;

        $phplist_config['viewbrowser_anonymous'] = true;
        $phplist_config['viewbrowser_allowed_lists'] = '';
        $phplist_config['date_format'] = 'j M Y';
        $translator = new \phpList\plugin\Common\FrontendTranslator(
            ['language_file' => 'german.inc'],
            $plugins['ViewBrowserPlugin']->coderoot
        );

        $archive = new phpList\plugin\ViewBrowserPlugin\ArchiveCreator($this->daoStub, $translator);
        $result = $archive->createListArchive(1);

        $this->assertStringContainsString('1 Okt. 2017', $result);
    }
}
