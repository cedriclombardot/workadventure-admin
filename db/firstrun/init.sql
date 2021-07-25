CREATE TABLE users (
  uuid varchar(36) NOT NULL,
  name varchar(30),
  email varchar(30),
  PRIMARY KEY (uuid)
);

CREATE TABLE tags (
  uuid varchar(36) NOT NULL,
  tag varchar(15) NOT NULL,
  PRIMARY KEY (uuid, tag)
);

CREATE TABLE reports (
    report_id integer NOT NULL AUTO_INCREMENT,
    reportedUserUuid varchar(36) NOT NULL,
    reportedUserComment varchar(1000) NOT NULL,
    reporterUserUuid varchar(36) NOT NULL,
    reportWorldSlug varchar(20) NOT NULL,
    PRIMARY KEY (report_id)
);

CREATE TABLE banned_users (
    uuid varchar(36) NOT NULL,
    ban_message varchar(50) NOT NULL,
    PRIMARY KEY (uuid)
);
