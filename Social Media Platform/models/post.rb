class Post
  include Neo4j::ActiveNode
  include Neo4j::Timestamps

  searchkick

  property :title, type: String
  validates :title, presence: true

  property :content, type: String
  validates :content, presence: true

  property :visible, type: Boolean, default: true
  property :rank_score, type: Float, default: 0.0
  property :rank_dirty, type: Boolean, default: true

  has_one :out, :author, type: :author, model_class: :User
  has_many :out, :tags, type: :posted_in, model_class: :Tag
  has_many :in, :comments, origin: :post, model_class: :Comment
  has_many :in, :votes, rel_class: :Vote
  has_many :in, :flags, rel_class: :Flag

  def has_comments?
    !comments.empty?
  end

  def has_votes?
    !votes.empty?
  end

  def rank
    return rank_score unless rank_dirty

    log_comments = [Math::log10(comments.count + 1.0), 1.0].max
    score = (author.rank * log_comments * avg_tag_rank * avg_score) / post_flags.to_f
    self.rank_score = score
    self.rank_dirty = false

    score
  end

  private

  def avg_tag_rank
    ranks = tags.collect { |tag| tag.rank }
    ranks.empty? ? 1.0 : ranks.sum / ranks.count.to_f
  end

  def avg_score
    scores = votes.rels.map { |vote| vote.score }
    scores.empty? ? 1.0 : scores.sum / scores.count.to_f
  end

  def post_flags
    types = flags.rels.map { |flag| flag.flag }
    types.empty? ? 1.0 : types.sum
  end
end
