import logging

# 設置日誌
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

logger.info("腳本開始執行")
print("腳本執行成功")
