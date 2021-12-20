# coding-status
Coding.net Status Dashboard

## tech

1. 编写「验收测试」，代码：[coding-sdk-php](https://github.com/coding/coding-sdk-php)
2. 在 CODING 持续集成中定时执行（每 5 分钟），代码：`Jenkinsfile`
   1. 运行验收测试
   2. 提交测试结果 `junit.xml`，推送到年份分支，比如 `2021`
   3. 根据 git log 生成 HTML，推送到 `gh-pages` 分支
