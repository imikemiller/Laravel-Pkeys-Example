<?php
/**
 * Created by PhpStorm.
 * User: mike
 * Date: 29/07/17
 * Time: 22:05
 *
 * Pkeys Schema
 */

return [
    /*
     * This key must be present
     */
    'schema'=>[
        'redis'=>[
            'user'=>[
                /*
                 * Must have the param `id` passed in and must be numeric
                 */
                'messages'=>'user:{id|numeric}:messages'
            ],
            'users'=>[
                /*
                 * Must have params `status` and `day` passed in.
                 * `status` must be either "active","new" or "returning"
                 * `day` must be a valid date
                 */
                'count'=>'users:{status|in:active,new,returning}:{day|date}:count'
            ],
            'events'=>[
                /*
                 * Must have the param `type` and must be either "new" or "read"
                 */
                'messages'=>'message-event-{type|in:new,read}'
            ]
        ],
        'cache'=>[

        ],
        'channels'=>[

        ],
        'events'=>[

        ]
    ],
    /*
     * Optional delimiters
     */
    //'delimiters=>[':','~','-,'.']
];