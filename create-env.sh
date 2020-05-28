#!/bin/bash

## FIle will be used for creating and launching instance

######### dynamo-db

aws dynamodb create-table --table-name RecordsCAN --attribute-definitions AttributeName=Receipt,AttributeType=S AttributeName=Email,AttributeType=S --key-schema AttributeName=Receipt,KeyType=HASH AttributeName=Email,KeyType=RANGE --provisioned-throughput ReadCapacityUnits=5,WriteCapacityUnits=5

aws dynamodb describe-table --table-name RecordsCAN

aws dynamodb wait table-exists --table-name RecordsCAN

echo " DB wait successful"

############ SQS

aws sqs create-queue --queue-name inclass-can

############# SNS

aws sns create-topic --name project-messages-can


########## load balancer

aws elb create-load-balancer --load-balancer-name can-load-balancer --listeners "Protocol=HTTP,LoadBalancerPort=80,InstanceProtocol=HTTP,InstancePort=80" --subnets $7 --security-groups $5

echo "load balancers done"


############# auto-scaling

# creating launch config

aws autoscaling create-launch-configuration --launch-configuration-name can-launch-config --image-id $1 --key-name $4 --instance-type $3 --security-groups $5 --iam-instance-profile $6 --user-data file://install-app-env-front-end.sh

echo "Create launch configuration created"

# creating autoscaling grp

aws autoscaling create-auto-scaling-group --auto-scaling-group-name can-asg --launch-configuration-name can-launch-config  --min-size 2 --max-size 4 --desired-capacity 3 --load-balancer-names can-load-balancer --availability-zones us-east-1c 

echo "Auto scaling group created"

aws ec2 wait instance-status-ok

#########################LAMBDA

aws lambda create-function --function-name final-trigger --runtime python3.7 --zip-file fileb://PIL.zip --handler process.lambda_handler --role $8

