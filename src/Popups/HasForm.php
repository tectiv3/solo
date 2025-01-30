<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Popups;

use AaronFrancis\Solo\Support\CapturedPromptInterface;
use Generator;

trait HasForm
{
    protected Generator $form;

    public function bootHasForm()
    {
        $this->form = $this->form();
    }

    abstract public function form(): Generator;

    public function renderForm()
    {
        $step = $this->form->current();

        if ($step instanceof CapturedPromptInterface) {
            $step->setScreen($this->screen);

            if ($step->isComplete()) {
                $this->form->next();

                return;
            }

            $step->renderSingleFrame();
        } elseif (is_string($step)) {
            $this->screen->writeln($step);
            $this->form->next();
        }
    }

    public function renderSingleFrame()
    {
        $this->renderForm();
    }

    public function handleFormInput($key)
    {
        if ($this->form->current() instanceof CapturedPromptInterface) {
            $this->form->current()->handleInput($key);
        }
    }
}
