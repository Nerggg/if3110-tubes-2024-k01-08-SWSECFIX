<?php

namespace src\controllers;

use src\exceptions\HttpExceptionFactory;

/**
 * Base controller 
 * Request & Response is not stored as property to make it stateless & singleton (inspired by NestJS default singleton lifecycle).
 * Also inspired by ExpressJS middleware/route handler signature.
 */
abstract class Controller
{

}
