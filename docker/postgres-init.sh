#!/bin/bash
# Roda uma única vez, na criação do volume.
# Os testes usam banco separado para nunca tocarem nos dados de desenvolvimento.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
    CREATE DATABASE hackathon_test OWNER $POSTGRES_USER;
EOSQL
