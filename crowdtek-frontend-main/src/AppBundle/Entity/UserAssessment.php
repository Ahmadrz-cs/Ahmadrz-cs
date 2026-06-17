<?php

namespace AppBundle\Entity;

class UserAssessment
{
    // public string $notes = null;
    /**
     * @var AssessmentResponse[]
     */
    public array $responses = [];
    public bool $complete = false;
}
