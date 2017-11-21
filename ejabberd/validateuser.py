#!/usr/bin/python

import sys
from struct import *
import logging
import requests
import urllib


URL = 'https://guideup.com.br/api/'

sys.stderr = open('/var/log/ejabberd/extauth_err4.log', 'w')
logging.basicConfig(level=logging.INFO,
                    format='%(asctime)s %(levelname)s %(message)s',
                    filename='/var/log/ejabberd/extauth_err4.log',
                    filemode='a')

logging.info('extauth script started, waiting for ejabberd requests')

def from_ejabberd():
    input_length = sys.stdin.read(2)
    (size,) = unpack('>h', input_length)
    data = sys.stdin.read(size)
    logging.info("Data Lenght: " + str(input_length))
    logging.info("Data sent: " + str(data))
    return data.split(':')

def to_ejabberd(bool):
    answer = 0
    if bool:
        answer = 1
    token = pack('>hh', 2, answer)
    sys.stdout.write(token)
    sys.stdout.flush()

def auth(username, server, password):
    logging.info("Entered auth with values:" +username+", "+server+", "+password)
    headers = {
        'Accept': 'application/json',
	'Authorization': 'Bearer '+ password
    }

    try:
        logging.info("Sendind auth url request")
        r = requests.get(URL + 'user/'+username.replace("[at]","@"), headers = headers, allow_redirects = False)
        logging.info("Auth Http response: %s" % r.text)
    except requests.exceptions.HTTPError as err:
        logging.warn("Http Error: %s" % err)
        return False
    except requests.exceptions.RequestException as err:
        try:
            logging.warn('An error occured during the request: %s' % err)
        except TypeError as err:
            logging.warn('An unknown error occured during the request, probably an SSL error. Try updating your "requests" and "urllib" libraries.')
        return False

    if r.status_code != requests.codes.ok:
        logging.info("Auth Http code differs than OK:" +r.status_code)
        return False

    json = r.json()
    if json['email'] == username.replace('[at]','@',1):
        return True

    logging.info("Auth not valid")
    return False

def isuser(username, server):
    logging.info("Entered isuser with values:" +username+", "+server)
    headers = {
        'Accept': 'application/json',
    }
    uri = 'user/0/'+username.replace('[at]','@',1)+'/exists'

    try:
        logging.info("Sending isuser uri:" +uri)
        r = requests.get(URL + uri, headers = headers, allow_redirects = False)
        logging.info("isuser Http respnse: %s" % r.text) 
    except requests.exceptions.HTTPError as err:
        logging.warn(err)
        return False
    except requests.exceptions.RequestException as err:
        try:
            logging.warn('An error occured during the request: %s' % err)
        except TypeError as err:
            logging.warn('An unknown error occured during the request, probably an SSL error. Try updating your "requests" and "urllib" libraries.')
        return False

    if r.status_code != requests.codes.ok:
        logging.info("Auth Http code differs than OK:" +r.status_code)
        return False

    json = r.json()
    logging.info("json response response " +str(json['ok']))
    response = str(json['ok']).lower() == 'true'
    logging.info("isuser response " +str(response))
    return response

def setpass(username, server, password):
    logging.info("Entered setpass with values:" +username+", "+server)
    return False

while True:
    data = from_ejabberd()
    success = False
    if data[0] == "auth":
        success = auth(data[1], data[2], data[3])
    elif data[0] == "isuser":
        success = isuser(data[1], data[2])
    elif data[0] == "setpass":
        success = setpass(data[1], data[2], data[3])
    to_ejabberd(success)
