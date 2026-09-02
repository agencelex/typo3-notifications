
#
# Table structure for table 'tx_lexnotifications_domain_model_notification'
#
CREATE TABLE tx_lexnotifications_domain_model_notification (
    type                                                        varchar(255) DEFAULT '' NOT NULL,
    notifiable_id                                               int(11) DEFAULT '0' NOT NULL,
    notifiable_type                                             varchar(255) DEFAULT '' NOT NULL,
    level                                                       int(11) DEFAULT '0' NOT NULL,
    data                                                        text,
    read_at                                                     int(11) DEFAULT '0' NOT NULL,

    KEY notifiable (notifiable_id, notifiable_type(255))
);

#
# Table structure for table 'tx_lexnotifications_domain_model_message'
#
CREATE TABLE tx_lexnotifications_domain_model_message (
    level                                                       int(11) DEFAULT '0' NOT NULL,
    subject                                                     varchar(255) DEFAULT '' NOT NULL,
    message                                                     text,
    receivers                                                   varchar(1024) DEFAULT '' NOT NULL,
    excluded_recipients                                         varchar(1024) DEFAULT '' NOT NULL,
    channels                                                    varchar(255) DEFAULT '' NOT NULL,
    cruser                                                      int(11) unsigned DEFAULT 0 NOT NULL,
    sent_at                                                     int(11) DEFAULT '0' NOT NULL,
);