class User
  include Neo4j::ActiveNode
  include Neo4j::Timestamps
  include Neo4jrb::Paperclip

  devise :database_authenticatable, :registerable, :recoverable, :rememberable, :trackable, :validatable

  after_save :join_default_group

  property :first_name, type: String, null: false, index: :exact
  validates :first_name, presence: true

  property :last_name, type: String, null: false, index: :exact
  validates :last_name, presence: true

  property :username, type: String, null: false, constraint: :unique
  validates :username, presence: true, uniqueness: true

  property :date_of_birth, type: Date
  validates :date_of_birth, presence: true

  property :email, type: String, null: false, constraint: :unique
  validates :email, presence: true, uniqueness: true

  property :bio, type: String, default: 'Temp bio data :)'
  property :encrypted_password
  property :public_name, type: Boolean, default: true
  property :public_date_of_birth, type: Boolean, default: true
  property :public_email, type: Boolean, default: true
  property :public_posts, type: Boolean, default: true

  property :rank_score, type: Float, default: 0.0
  property :rank_dirty, type: Boolean, default: true

  property :remember_created_at, type: DateTime
  property :reset_password_token
  property :reset_password_sent_at, type: DateTime
  property :sign_in_count, type: Integer, default: 0
  property :current_sign_in_at, type: DateTime
  property :last_sign_in_at, type: DateTime
  property :current_sign_in_ip, type: String
  property :last_sign_in_ip, type: String

  has_many :out, :groups, origin: :users, model_class: :Group
  has_many :out, :likes, origin: :likes, model_class: :Tag
  has_many :in, :posts, origin: :author, model_class: :Post
  has_many :out, :votes, origin: :votes, model_class: :Post
  has_many :in, :comments, origin: :author, model_class: :Comment
  has_many :in, :notifications, origin: :recipient, model_class: :Notification
  has_many :in, :followers, type: :follows, model_class: :User
  has_many :out, :following, type: :follows, model_class: :User

  has_neo4jrb_attached_file :avatar,
    styles: { medium: '300x300>', thumb: '100x100#' },
    default_url: '/images/:style/missing.png',
    size: { in: 0..10.megabytes }

  validates_attachment_content_type :avatar, content_type: /\Aimage\/.*\Z/

  def self.recommended(user)
    users = user.following.map { |u| u.following }.flatten
    users - user.following - [user]
  end

  def show_name?(user)
    return true if public_name || self.eql?(user)
    followers.to_a.include?(user)
  end

  def show_date_of_birth?(user)
    return true if public_date_of_birth || self.eql?(user)
    followers.to_a.include?(user)
  end

  def show_email?(user)
    return true if public_email || self.eql?(user)
    followers.to_a.include?(user)
  end

  def show_posts?(user)
    return true if public_posts || self.eql?(user)
    followers.to_a.include?(user)
  end

  def is_admin?
    group = Group.find_by(name: 'Administrator')
    groups.to_a.include?(group)
  end

  def is_moderator?
    group = Group.find_by(name: 'Moderator')
    groups.to_a.include?(group)
  end

  def join_group(group)
    group = Group.find_by(name: group)

    if nil != group && !groups.to_a.include?(group)
      groups << group
    end
  end

  def leave_group(group)
    group = Group.find_by(name: group)

    if nil != group && groups.to_a.include?(group)
      groups.delete(group)
    end
  end

  def rank
    return rank_score unless rank_dirty

    score = (posts.count * activity) / posts_flags.to_f
    self.rank_score = score
    self.rank_dirty = false

    score
  end

  private

  def join_default_group
    join_group('Standard')
  end

  def activity
    log_comments = Math::log10(comments.count + 1.0)
    log_votes = Math::log10(votes.count + 1.0)

    [log_comments * log_votes, 1.0].max
  end

  def posts_flags
    flags = posts.collect { |post| post.flags.rels }.flatten
    flags.empty? ? 1.0 : flags.map { |flag| flag.flag }.sum
  end
end
