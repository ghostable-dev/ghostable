<?php

namespace App\Blog\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

class YouTubeEmbedParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::oneOf('@');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $previousState = $cursor->saveState();
        $cursor->advance();

        if (! $cursor->match('/\[youtube\]/')) {
            $cursor->restoreState($previousState);

            return false;
        }

        if (! $cursor->match("/\(/")) {
            $cursor->restoreState($previousState);

            return false;
        }

        if (empty($url = $cursor->match('/^([^)]+)\)/'))) {
            $cursor->restoreState($previousState);

            return false;
        }

        $iframeHtml = sprintf(
            '<iframe class="w-full" height="400" src="%s" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
            $url
        );

        $inlineContext->getContainer()
            ->appendChild(new HtmlInline($iframeHtml));

        return true;
    }
}
