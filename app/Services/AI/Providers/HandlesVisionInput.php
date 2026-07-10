<?php

namespace App\Services\AI\Providers;

trait HandlesVisionInput
{
    private function hasVisionContent(array $userMessage): bool
    {
        foreach ($userMessage as $part) {
            if (($part['type'] ?? '') === 'image') {
                return true;
            }
        }
        return false;
    }

    /**
     * Converts the controller's Anthropic-style content array to OpenAI vision format.
     * Input:  [['type'=>'image','source'=>['media_type'=>'image/jpeg','data'=>'...']], ['type'=>'text','text'=>'...']]
     * Output: [['type'=>'image_url','image_url'=>['url'=>'data:image/jpeg;base64,...']], ['type'=>'text','text'=>'...']]
     */
    private function toOpenAiVisionContent(array $userMessage): array
    {
        $content = [];
        foreach ($userMessage as $part) {
            if (($part['type'] ?? '') === 'image') {
                $src       = $part['source'];
                $mediaType = $src['media_type'] ?? 'image/jpeg';
                $data      = $src['data'] ?? '';
                $content[] = ['type' => 'image_url', 'image_url' => ['url' => "data:{$mediaType};base64,{$data}"]];
            } elseif (($part['type'] ?? '') === 'text') {
                $content[] = ['type' => 'text', 'text' => $part['text']];
            }
        }
        return $content;
    }
}
