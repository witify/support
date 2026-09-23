<?php

return [

    /*
     * Prefix of the admin SPA. IsResourceTrait builds `resource_data.admin_url`
     * from it and from the model's getResourceAdminTo().
     */
    'admin_url' => '/admin',

    /*
     * Name that signs the notification mails, `app.name` when null. The
     * application usually resolves it from its settings with
     * Host::resolveCompanyNameUsing().
     */
    'company_name' => null,

    'notifications' => [
        /*
         * Private channel that receives `notifications.created` when the
         * database channel stores a notification. `{id}` is the notifiable key.
         */
        'channel' => 'user.{id}',

        /*
         * Set to false in applications without a WebSocket server.
         */
        'broadcast' => true,
    ],

];
