from time import time
from .defunctions import *
import requests as req
import json


class Chats:

    def __init__(self, whose="WA", **kwargs):

        self.name = whose

        self.secret = kwargs['secret'] if "secret" in kwargs.keys() else "94cd079cf61718af1bd2cc2e812bfe2fadc5fff3"
        self.channel_id = kwargs['channel_id'] if "channel_id" in kwargs.keys() else "df5a20c8-646f-498a-8410-393f2a28dfc6"
        self.scope_id = kwargs['scope_id'] if "scope_id" in kwargs.keys() else "df5a20c8-646f-498a-8410-393f2a28dfc6_" \
                                              "5ab23be7-8a30-4fe3-8116-fb96b994671c"
        self.account_id = kwargs['account_id'] if "account_id" in kwargs.keys() else "5ab23be7-8a30-4fe3-8116-fb96b994671c"

    def send_text(self, chat_id: str = uuqo(32), sender: str = "1", message: str = "Hello",
                  name="Neurobot", avatar="", phone="79088223746"):
        body = {
            "event_type": 'new_message',
            "payload": {
                "timestamp": round(time()),
                "msgid": uuqo(8),
                "conversation_id": chat_id,
                "sender": {
                    "id": uuqo(8),
                    "name": name,
                    "avatar": avatar,
                    "profile": {
                        "phone": phone
                    }
                },
                "message": {
                    "type": "text",
                    "text": message
                },
                "silent": False
            }
        }

        signature = get_signature("sha1", json.dumps(body), self.secret).hexdigest()
        link = f"https://amojo.amocrm.ru/v2/origin/custom/{self.scope_id}"
        HEADERS = {
            "cache-control": "no-cache",
            "content-type": "application/json",
            "x-signature": f"{signature}"
        }

        return req.post(link, headers=HEADERS, data=json.dumps(body))

    def send_photo(self, chat_id: str = uuqo(32), link: str = "https://crm.yasdelayu.ru/amocrmSalers/logo.png",
                  name="Neurobot", avatar="", phone="79088223746"):

        body = {
            "event_type": 'new_message',
            "payload": {
                "timestamp": round(time()),
                "msgid": uuqo(8),
                "conversation_id": chat_id,
                "sender": {
                    "id": uuqo(8),
                    "name": name,
                    "avatar": avatar,
                    "profile": {
                        "phone": phone
                    }
                },
                "message": {
                    "type": "picture",
                    "text": link,
                    "file_name": f"Image from {self.name}",
                    "file_size": 10200
                },
                "silent": False
            }
        }

        signature = get_signature("sha1", json.dumps(body), self.secret).hexdigest()

        link = f"https://amojo.amocrm.ru/v2/origin/custom/{self.scope_id}"
        HEADERS = {
            "cache-control": "no-cache",
            "content-type": "application/json",
            "x-signature": f"{signature}"
        }

        return req.post(link, headers=HEADERS, data=json.dumps(body))

    def edit_status_message(self, message_id: str, status: int = 0, **kwargs):

        body = {
            "msgid": message_id,
            "delivery_status": status
        }
        if kwargs:
            for key in kwargs.keys():
                body[key] = kwargs[key]

        signature = get_signature("sha1", json.dumps(body), self.secret).hexdigest()

        link = f"https://amojo.amocrm.ru/v2/origin/custom/{self.scope_id}/{message_id}/delivery_status"
        HEADERS = {
            "cache-control": "no-cache",
            "content-type": "application/json",
            "x-signature": f"{signature}"
        }

        return req.post(link, headers=HEADERS, data=json.dumps(body))
