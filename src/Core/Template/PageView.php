<?php

namespace App\Core\Template;

use App\Core\Http\Response;

final class PageView
{
    private string $layout;
    private string $mainTemplate;
    private ?string $sectionTemplate;
    /** @var array<string, mixed> */
    private array $variables;

    public function __construct(string $layout, string $mainTemplate, ?string $sectionTemplate = null, array $variables = [])
    {
        $this->layout = $layout;
        $this->mainTemplate = $mainTemplate;
        $this->sectionTemplate = $sectionTemplate;
        $this->variables = $variables;
    }

    public function withVariables(array $variables): self
    {
        $clone = clone $this;
        $clone->variables = array_merge($this->variables, $variables);

        return $clone;
    }

    public function render(TemplateRenderer $renderer): Response
    {
        $data = $this->variables;

        if ($this->sectionTemplate !== null) {
            $data['sectionContent'] = $renderer->render($this->sectionTemplate, $data);
        }

        $data['mainContentHtml'] = $renderer->render($this->mainTemplate, $data);

        $content = $renderer->render($this->layout, $data);

        return new Response($content);
    }
}