<?php

namespace GardenLawn\Company\Enum;

enum Status: int
{
    case None = 0;
    case New = 1;
    case CustomerCreate = 4;
}
