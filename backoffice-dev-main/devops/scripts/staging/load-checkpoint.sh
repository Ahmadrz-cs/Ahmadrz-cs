STAGING_S3_BUCKET=`aws --region=eu-west-2 ssm get-parameter --name "/devops/s3/staging-dumps" --output text --query Parameter.Value`

echo "$(date +%F' '%T) Get latest checkpoint"
LATEST_CHECKPOINT=`aws s3 ls s3://$STAGING_S3_BUCKET/checkpoints/checkpoint-dump- | sort | tail -n -1 | awk '{print $4}'`

if [ -z $LATEST_CHECKPOINT ]; then
    echo "$(date +%F' '%T) No checkpoint dump found."
    exit 1
else
    echo "$(date +%F' '%T) Using latest checkpoint $LATEST_CHECKPOINT"
fi

CHECKPOINT_IDENTIFIER="checkpoint-$(date +%F)"

echo "$(date +%F' '%T) Downloading and unzipping most recent checkpoint"
aws s3 cp s3://$STAGING_S3_BUCKET/checkpoints/$LATEST_CHECKPOINT $CHECKPOINT_IDENTIFIER.sql.gz
gunzip $CHECKPOINT_IDENTIFIER.sql.gz

DB_USER=`aws --region=eu-west-2 ssm get-parameter --name "/devops/db/user" --output text --query Parameter.Value`
DB_HOST=`aws --region=eu-west-2 ssm get-parameter --name "/devops/db/host" --output text --query Parameter.Value`
DB_PW=`aws --region=eu-west-2 ssm get-parameter --name "/devops/db/password" --with-decryption --output text --query Parameter.Value`
DB_NAME=`aws --region=eu-west-2 ssm get-parameter --name "/devops/db/dbname/staging" --output text --query Parameter.Value`

echo "$(date +%F' '%T) Loading checkpoint dump onto staging db"
mysql -u $DB_USER -h $DB_HOST -p$DB_PW $DB_NAME --ssl-ca=/home/eu-west-2-bundle.pem --ssl-verify-server-cert < $CHECKPOINT_IDENTIFIER.sql

echo "$(date +%F' '%T) Finished loading checkpoint $CHECKPOINT_IDENTIFIER onto staging db"