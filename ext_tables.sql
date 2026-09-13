
#
# Table structure for table 'tx_lexnotifications_domain_model_notification'
#
CREATE TABLE tx_lexnotifications_domain_model_notification (
    type            varchar(255) DEFAULT '' NOT NULL,
    notifiable_id   int DEFAULT 0 NOT NULL,
    notifiable_type varchar(255) DEFAULT '' NOT NULL,
    level           int DEFAULT 0 NOT NULL,
    data            mediumtext,
    read_at         int DEFAULT 0 NOT NULL,

    KEY notifiable (notifiable_id, notifiable_type(255))
);

#
# Table structure for table 'tx_lexnotifications_domain_model_message'
#
CREATE TABLE tx_lexnotifications_domain_model_message (
    level               int DEFAULT 0 NOT NULL,
    subject             varchar(255) DEFAULT '' NOT NULL,
    message             mediumtext,
    link                varchar(2048) DEFAULT '' NOT NULL,
    receivers           varchar(1024) DEFAULT '' NOT NULL,
    excluded_recipients varchar(1024) DEFAULT '' NOT NULL,
    channels            varchar(255) DEFAULT '' NOT NULL,
    cruser              int unsigned DEFAULT 0 NOT NULL,
    sent_at             int DEFAULT 0 NOT NULL
);
