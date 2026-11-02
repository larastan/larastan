<?php

namespace ConfigFileScopeDependency;

function test(): void
{
    config('scopereader.v');
    config('scopereader.inline');
}
