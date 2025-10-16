<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Response;

class RssController extends Controller
{
    public function feed(): Response
    {
        $site = config('app.name', 'Bard Library');
        $url = config('app.url', 'http://localhost');
        $items = Book::orderByDesc('id')->limit(50)->get();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel/></rss>');
        $channel = $xml->channel;
        $channel->addChild('title', htmlspecialchars($site));
        $channel->addChild('link', $url);
        $channel->addChild('description', htmlspecialchars($site.' - 最近新增书籍'));

        foreach ($items as $b) {
            $it = $channel->addChild('item');
            $it->addChild('title', htmlspecialchars($b->title));
            $it->addChild('link', $url.'/books/'.$b->id);
            $it->addChild('guid', (string)$b->id);
            if ($b->description) {
                $it->addChild('description', htmlspecialchars(mb_substr($b->description, 0, 500)));
            }
            if ($b->created_at) {
                $it->addChild('pubDate', $b->created_at->toRfc2822String());
            }
        }

        return new Response($xml->asXML(), 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
