#!/bin/bash

####################### Delete SQS Queue

SQSURL=`aws sqs list-queues --queue-name-prefix inclass-can | awk {'print $2'}`
aws sqs delete-queue --queue-url $SQSURL

echo "DELETED SQS Queue"

######################## Delete SNS topic

TOPICARN=`aws sns list-topics | awk {'print $2'}`
aws sns delete-topic --topic-arn $TOPICARN

echo " DELETED SNS TOPIC"


###################### delete dynamodb instance
aws dynamodb delete-table --table-name RecordsCAN

aws dynamodb wait table-not-exists --table-name RecordsCAN

echo "DELETED dynamodb table"

######################### delete auto scaling

#aws autoscaling terminate-instance-in-auto-scaling-group --instance-id $(aws ec2 describe-instances --filters "Name=instance-state-name,Values=running " --query "Reservations[].Instances[].InstanceId")

#echo "delete instances connected"

#aws autoscaling detach-instances --auto-scaling-group-name $(aws autoscaling describe-auto-scaling-groups --query 'AutoScalingGroups[*][AutoScalingGroupName]') --instance-ids $(aws ec2 describe-instances --filters "Name=instance-state-name,Values=running " --query "Reservations[].Instances[].InstanceId") --no-should-decrement-desired-capacity

#aws autoscaling delete-auto-scaling-group --auto-scaling-group-name $(aws autoscaling describe-auto-scaling-groups --query 'AutoScalingGroups[*][AutoScalingGroupName]')
aws autoscaling delete-auto-scaling-group --auto-scaling-group-name $(aws autoscaling describe-auto-scaling-groups --query 'AutoScalingGroups[*][AutoScalingGroupName]') --force-delete

echo "Deleted auto scaling group"

aws autoscaling delete-launch-configuration --launch-configuration-name $(aws autoscaling describe-launch-configurations --query 'LaunchConfigurations[*][LaunchConfigurationName]')

echo "Deleted launch config for auto scaling group"

############################# destroying lambda function
 
aws lambda delete-function --function-name $(aws lambda list-functions --query Functions[].FunctionName[]) 

echo "Deleted Lambda Function"

########################  terminate load balancers

aws elb deregister-instances-from-load-balancer --load-balancer-name $(aws elb describe-load-balancers --output text --query 'LoadBalancerDescriptions[].[LoadBalancerName]') --instances $(aws ec2 describe-instances --filters "Name=instance-state-name,Values=running" --query "Reservations[].Instances[].InstanceId")

#echo " de-registered  instances from load balancers"

aws elb delete-load-balancer --load-balancer-name $( aws elb describe-load-balancers --output text --query 'LoadBalancerDescriptions[].[LoadBalancerName]')

echo "deleted load balancers"


