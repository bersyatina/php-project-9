CREATE TABLE IF NOT EXISTS urls
(
    id serial PRIMARY KEY,
    name character varying (255) NOT NULL UNIQUE,
    created_at timestamp
);

CREATE TABLE IF NOT EXISTS url_checks (
    id serial PRIMARY KEY,
    url_id bigint REFERENCES urls (id),
    status_code smallint,
    h1 character varying(255),
    title character varying(255),
    description character varying(255),
    name character varying(255),
    created_at timestamp
);