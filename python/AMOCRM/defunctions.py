from random import randint
import hmac
import hashlib


def uuqo(largest: int = 8, is_num: bool = False):
    alphabet = "qwertyuiopasdfghjklzxcvbnmQWERTYUIOOPASDFGHJKLZXCVBNM"
    nums = "12345790"

    total_items = (alphabet + nums, nums)[is_num]

    uni = ""
    for _ in range(largest):
        uni = f"{uni}{total_items[randint(0, len(total_items)-1)]}"

    return uni


def get_signature(type = "sha1", data: str = "", key: str = ""):

    return hmac.new(key.encode(), data.encode(), hashlib.sha1)
