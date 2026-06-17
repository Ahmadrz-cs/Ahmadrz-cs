<?php

namespace AppBundle\Entity;

class AssessmentResponse
{
    public ?Question $question = null;
    public ?QuestionChoice $choice = null;
}
