<?php

namespace ViewStringContent;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Content as MailContent;

function content(?string $dynamic): void
{
    new Content(view: 'missing-content-view');
    new Content(html: 'missing-content-html');
    new Content(text: 'missing-content-text');
    new Content(markdown: 'missing-content-markdown');
    new Content('missing-positional-view', 'missing-positional-html', 'missing-positional-text', 'missing-positional-markdown');
    new MailContent(view: 'missing-aliased-view');

    new Content(
        markdown: 'emails.mailable.markdown',
        text: 'emails.mailable.view',
        html: 'home',
        view: 'home',
        with: ['title' => 'This is data, not a view'],
        htmlString: '<h1>Rendered HTML</h1>',
    );
    new Content('home', 'home', 'home', 'home', [], '<p>Rendered HTML</p>');
    new Content();
    new Content(view: null, html: null, text: null, markdown: null, htmlString: null);
    new Content(view: $dynamic, html: $dynamic, text: $dynamic, markdown: $dynamic);
    new Content(view: 'emails.*');
    new Content(with: [], markdown: 'home', text: 'missing-reordered-text', view: 'home');

    if ($dynamic === null || view()->exists($dynamic)) {
        new Content(view: $dynamic, html: $dynamic, text: $dynamic, markdown: $dynamic);
    }
}
