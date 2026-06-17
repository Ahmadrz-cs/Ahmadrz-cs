STAGING_S3_BUCKET=`aws --region=eu-west-2 ssm get-parameter --name "/devops/s3/staging-dumps" --output text --query Parameter.Value`

echo "$(date +%F' '%T) Get latest schema and data"
LATEST_SCHEMA=`aws s3 ls s3://$STAGING_S3_BUCKET/tmp/forstaging_schema | sort | tail -n -1 | awk '{print $4}'`
LATEST_DATA=`aws s3 ls s3://$STAGING_S3_BUCKET/tmp/forstaging_data | sort | tail -n -1 | awk '{print $4}'`

if [ -z $LATEST_SCHEMA ] || [ -z $LATEST_DATA ]; then
    echo "$(date +%F' '%T) Schema or data dumps not found"
    exit 1
else
    echo "$(date +%F' '%T) Using latest schema $LATEST_SCHEMA and latest data $LATEST_DATA"
fi

SCHEMA_IDENTIFIER="schema-$(date +%F)"
DATA_IDENTIFIER="data-$(date +%F)"

echo "$(date +%F' '%T) Downloading and unzipping most recent schema"
aws s3 cp s3://$STAGING_S3_BUCKET/tmp/$LATEST_SCHEMA $SCHEMA_IDENTIFIER.sql.gz
gunzip $SCHEMA_IDENTIFIER.sql.gz
echo "$(date +%F' '%T) Downloading and unzipping most recent data"
aws s3 cp s3://$STAGING_S3_BUCKET/tmp/$LATEST_DATA $DATA_IDENTIFIER.sql.gz
gunzip $DATA_IDENTIFIER.sql.gz

DB_USER=`aws --region=eu-west-2 ssm get-parameter --name "/devops/db/user" --output text --query Parameter.Value`
DB_HOST=`aws --region=eu-west-2 ssm get-parameter --name "/devops/db/host" --output text --query Parameter.Value`
DB_PW=`aws --region=eu-west-2 ssm get-parameter --name "/devops/db/password" --with-decryption --output text --query Parameter.Value`
DB_NAME=`aws --region=eu-west-2 ssm get-parameter --name "/devops/db/dbname/staging" --output text --query Parameter.Value`

echo "$(date +%F' '%T) Loading schema onto staging db"
mysql -u $DB_USER -h $DB_HOST -p$DB_PW $DB_NAME --ssl-ca=/home/eu-west-2-bundle.pem --ssl-verify-server-cert < $SCHEMA_IDENTIFIER.sql
echo "$(date +%F' '%T) Loading data onto staging db"
mysql -u $DB_USER -h $DB_HOST -p$DB_PW $DB_NAME --ssl-ca=/home/eu-west-2-bundle.pem --ssl-verify-server-cert < $DATA_IDENTIFIER.sql

echo "$(date +%F' '%T) Apply staging db prep and cleanup"
mysql -u $DB_USER -h $DB_HOST -p$DB_PW $DB_NAME --ssl-ca=/home/eu-west-2-bundle.pem --ssl-verify-server-cert < devops/scripts/staging/prep_and_clean.sql

CHECKPOINT_DUMP_IDENTIFIER="checkpoint-dump-$(date +%F_%T)"

echo "$(date +%F' '%T) Creating new staging database checkpoint"
# --column-statistics=0 only needed for mysql 8+ client not mariadb client
mysqldump -u $DB_USER -h $DB_HOST -p$DB_PW $DB_NAME --ssl-ca=/home/eu-west-2-bundle.pem --ssl-verify-server-cert > $CHECKPOINT_DUMP_IDENTIFIER.sql
ls -lh $CHECKPOINT_DUMP_IDENTIFIER.sql
gzip $CHECKPOINT_DUMP_IDENTIFIER.sql
ls -lh $CHECKPOINT_DUMP_IDENTIFIER.sql*
aws s3 mv $CHECKPOINT_DUMP_IDENTIFIER.sql.gz s3://$STAGING_S3_BUCKET/checkpoints/

echo "$(date +%F' '%T) Checkpoint dump uploaded: $CHECKPOINT_DUMP_IDENTIFIER.sql.gz"
