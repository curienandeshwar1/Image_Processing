import json
import boto3
import os
import glob
from PIL import Image , ImageFilter

def lambda_handler(event, context):
    
    s3 = boto3.client('s3')
    message = event['Records'][0]['Sns']['Message']
    receipt = message.split('&')[0]
    key = message.split('&')[1]
    email = message.split('&')[2]

    print(receipt)
    print(key)
    print(email)

    path = '/tmp/output'
    s3.download_file('can-2019-s3-bucket', key, path)
    
    im = Image.open(path) #enter your filename
    size = (100, 100)
    im.thumbnail(size)
    #background = Image.new('RGBA', size, (255, 255, 255, 0))
    #background.paste( im, (int((size[0] - im.size[0]) / 2), int((size[1] - im.size[1]) / 2)))
    im.save(path + ".png") 
    print("uploading:  " + path +".png")
    s3.upload_file(path + ".png", 'can-afterimg-process', key, ExtraArgs={'ACL':'public-read'})


    url = 'https://%s.s3.amazonaws.com/%s' % ('can-afterimg-process' , key)
    print (url)
    
    # https://boto3.amazonaws.com/v1/documentation/api/latest/guide/dynamodb.html
    # Get the service resource.
    
    dbclient = boto3.client('dynamodb')
    print ("Start update")
    # https://boto3.amazonaws.com/v1/documentation/api/latest/guide/dynamodb.html#updating-item
    dbclient.update_item(
        ExpressionAttributeNames={
            '#AT': 'S3finishedurl',
            '#YT': 'Status',
            '#IS' : 'Issubscribed',
        },
        ExpressionAttributeValues={
            ':t': {
                'S': url,
            },
            ':y': {
                'BOOL': True,
            },
            ':s': {
                'BOOL' : True,
                },
        },
        Key={
            'Receipt': {
                'S': receipt,
            },
            'Email': {
                'S': email,
            },
        },
        ReturnValues='ALL_NEW',
        TableName='RecordsCAN',
        UpdateExpression='SET #AT = :t, #YT = :y, #IS = :s',
    )
        
    os.remove(path)
    print ("Updated table")

    
    dynamodb = boto3.resource('dynamodb')
    table = dynamodb.Table('RecordsCAN')
    response1 = table.get_item(
        Key={
            'Receipt': receipt,
            'Email': email
            }
        )
    print (response1)
    phoneno = response1['Item']['Phone']
    print (phoneno)


    sns = boto3.client('sns')
    
    sns.publish(PhoneNumber = phoneno , Message = 'You can access your URL here : ' + url)

    #message.delete()
    #print("Message deleted")
    
