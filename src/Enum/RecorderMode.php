<?php

namespace Symfony\HttpClientRecorderBundle\Enum;

enum RecorderMode: string
{
    /**
     * Records all HTTP requests into the HAR file.
     */
    case Record = 'record';

    /**
     * Replays HTTP requests from the HAR file.
     */
    case Playback = 'playback';

    /**
     * Tries to replay requests from the HAR file, otherwise records them if no match is found.
     */
    case NewEpisodes = 'new_episodes';

    /**
     * Completely ignores the recording system and executes requests normally.
     */
    case PassThrough = 'passthrough';
}
