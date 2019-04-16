class Tag
  include Neo4j::ActiveNode
  include Neo4j::Timestamps

  searchkick

  property :name, type: String, constraint: :unique
  validates :name, presence: true

  property :rank_score, type: Float, default: 0.0
  property :rank_dirty, type: Boolean, default: true

  has_many :out, :parents, type: :parent, model_class: :Tag
  has_many :in, :children, type: :parent, model_class: :Tag
  has_many :in, :posts, origin: :tags, model_class: :Post
  has_many :in, :likes, type: :like, model_class: :User

  def self.recent(name)
    query_as(:t)
      .where('t.created_at > {date} AND t.name = {name}')
      .params(date: 30.days.ago.to_i, name: name)
      .pluck(:t)
  end

  def self.recommended(user)
    parents = user.likes.collect { |tag| tag.parents }
    parents.map { |tag| tag.children }.flatten - user.likes
  end

  def rank
    return rank_score unless rank_dirty

    recent_count = Tag.recent(name).count
    log_recent = [Math::log10(recent_count + 1.0), 1.0].max
    score = posts.count * log_recent
    self.rank_score = score
    self.rank_dirty = false

    score
  end
end