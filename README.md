# Tenjisagi（展示詐欺）

> スタート展示で見せた顔と、本番で見せる顔は、同じとは限らない。

## これは何か？

[Boatrace Open API](https://boatraceopenapi.github.io/api/) のデータをもとに、選手ごとに「スタート展示の進入コース」と「本番の進入コース」がどれくらい食い違っているかを集計して、その結果を眺めるための静的サイトです。

ボートレースファンの間でたまに囁かれる通称「展示詐欺」——展示で見せたコース取りと本番のコース取りが違う、あの現象——を、雰囲気や印象論ではなくちゃんと数字にしてみようという企画です。

## ランキングはこちら

**[https://shimomo.github.io/tenjisagi/](https://shimomo.github.io/tenjisagi/)**

`docs/` 以下に生成されるページを、そのまま GitHub Pages で公開しています。フレームワークもビルドツールも無し、素の HTML・CSS・JS だけで動く質素な作りです。

### 画面で見られるもの

- **順位**: 進入変化率が高い順（同率は同順位）
- **登録番号 / 選手名**
- **レース数**: 集計対象になったレース数
- **進入変化数 / 進入変化率**: 本番の進入がスタート展示と異なった回数・割合
- 登録番号・選手名でのその場検索、列見出しクリックでの並び替え

### 集計ルール（念のための言い訳コーナー）

- 対象は**直近1年分・かつ30走以上**走った選手のみ。「1走しかしていないのに変化率100%で1位」という事故を防ぐための最低限のガードです
- 引退・長期休養中の選手は、レースを重ねるたびにウィンドウ（直近1年）の外側へ自然と押し出されて表から消えていきます
- あくまでデータを機械的に集計しただけの参考情報です。特定の選手を非難する意図は一切なく、「へー、こんなに違うんだ」を楽しむための統計・ネタとして眺めてください

## 裏側の仕組み（気になる人向け）

| スクリプト | 役割 |
| --- | --- |
| `bin/analyze-course-changes.php [date] [directory]` | 指定日のレース結果を取得・集計し、`docs/course-changes/{Ymd}.json` に保存 |
| `bin/aggregate-course-changes.php [directory] [path]` | 日次ファイルを直近1年・30走以上の条件で集計して、ランキングページが読む `docs/course-changes-summary.json` を再生成 |

```
src/
├── ProgramsFetcher.php                 # Boatrace Open API から出走表・直前情報・結果を取得
├── CourseChangeAnalyzer.php            # 1日分のレースから進入変化を集計
├── CourseChangeRepository.php          # 日次集計を JSON として保存
├── CourseChangeAggregator.php          # 日次 JSON を直近1年・30走以上の条件で再集計
├── CourseChangeSummaryRepository.php   # 再集計結果をランキング用 JSON として保存
├── JsonDecoder.php / JsonEncoder.php   # JSON の読み書き共通処理
```

## ライセンス

Tenjisagi は [MIT license](LICENSE) の元で公開されています。
