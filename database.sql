CREATE TABLE client
(
    id       INT(128)    NOT NULL PRIMARY KEY,
    name     VARCHAR(64) NOT NULL,
    password VARCHAR(64) NOT NULL
) COMMENT 'Table that holds information about the clients';

CREATE TABLE product
(
    id             INT(128)     NOT NULL PRIMARY KEY,
    name           VARCHAR(128) NOT NULL,
    price          INT(255)     NOT NULL,
    image_location VARCHAR(128) NOT NULL,
    description    VARCHAR(512) NOT NULL
) COMMENT 'Table that holds the information about products';

CREATE TABLE client_product
(
    id_client  INT(128) NOT NULL,
    id_product INT(128) NOT NULL,
    amount     INT(128) NOT NULL,
    PRIMARY KEY (id_client, id_product),
    CONSTRAINT client_id_fk FOREIGN KEY (id_client) REFERENCES client (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT product_id_fk FOREIGN KEY (id_product) REFERENCES product (id) ON UPDATE CASCADE ON DELETE CASCADE
) COMMENT 'Table with relation between clients and products';
